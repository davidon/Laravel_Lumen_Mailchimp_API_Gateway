<?php
declare(strict_types=1);

namespace Tests\App\Functional\Http\Controllers\MailChimp;

use Tests\App\TestCases\MailChimp\MemberTestCase;
use Mailchimp\Mailchimp;
use App\Http\Controllers\MailChimp\MembersController;

class MembersControllerTest extends MemberTestCase
{
    /**
     * @var array
     */
    protected $createdMemberIds = [];

    /**
     * Call MailChimp to delete members created during test.
     * (This is only needed for functional members test)
     *
     * @return void
     */
    public function tearDown(): void
    {
        /** @var Mailchimp $mailChimp */
        $mailChimp = $this->app->make(Mailchimp::class);

        foreach ($this->createdlistIds as $listId ) {
            // Delete list on MailChimp after test
            $mailChimp->delete(\sprintf('lists/%s', $listId));

            //possibly member cannot be created after list is created
            $memberIds = $this->createdMemberIds['$listId'] ?? array();
            foreach ($memberIds as $memberId) {
                // Delete member on MailChimp after test
                $mailChimp->delete($this->getMemberUri($listId, $memberId));
            }
        }

        parent::tearDown();
    }

    /**
     * Test application creates successfully member and returns it back with id from MailChimp.
     *
     * @return void
     */
    public function testCreateMemberSuccessfully(): void
    {
        $this->createMailchimpListMember($list, $member);

        $this->assertResponseOk();
        $this->seeJson(static::$memberData);
        self::assertArrayHasKey('mail_chimp_id', $member);
        self::assertNotNull($member['mail_chimp_id']);
    }

    /**
     * @param array|null $list output of list response
     * @param array|null $member output of member response
     * @return bool
     */
    private function createMailchimpListMember(?array &$list = null, ?array &$member = null)
    {
        $this->createMailchimpList($list);
        //possibly member cannot be created after list is created
        $this->createdlistIds[] = $list['mail_chimp_id'];

		//generate a random email address so that Mailchimp server doesn't block member creation
		//must use static::$memberData as the revised email address in it will be used for assertion later
		static::$memberData['email_address'] = uniqid() . '@gmail.com';
        //create member
        $this->post(sprintf(self::PRE_URI, $list['list_id']), static::$memberData);
        $this->checkAccountBlocked();

        $member = \json_decode($this->response->getContent(), true);

		if (!empty($member['mail_chimp_id'])) {
			//Don't store mail_chimp_id instead of member_id
			$this->createdMemberIds[$list['mail_chimp_id']][] = $member['mail_chimp_id']; // Store MailChimp member id for cleaning purposes
			return true;
		}

		//not created list successfully
        return false;
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testCreateMemberValidationFailed(): void
    {
        $this->createMailchimpList($list);

        //create member without data
		$data = [];
		$data['email_address'] = 'RoyaltyCoLtd';
		$data['ip_signup'] = 'abc.def.245.865';
        $this->post(sprintf(self::PRE_URI, $list['list_id']), $data);
        $content = \json_decode($this->response->getContent(), true);

        $this->assertResponseStatus(self::HTTP_STATUS_BAD_REQUEST);
        self::assertArrayHasKey('message', $content);
        self::assertArrayHasKey('errors', $content);
        self::assertEquals('Invalid data given', $content['message']);

		self::assertArrayHasKey('email_address', $content['errors']);
		self::assertArrayHasKey('ip_signup', $content['errors']);

		$notRequired = static::$notRequired + ['email_address', 'ip_signup'];
        foreach (\array_keys(static::$memberData) as $key) {
            if (\in_array($key, $notRequired, true)) {
                continue;
            }

            self::assertArrayHasKey($key, $content['errors']);
        }
    }

	/**
	 * Test application returns error response when email address is duplicate under the same list
	 */
    public function testCreateMemberDuplicateEmailFailed(): void
	{
		if (!$this->createMailchimpListMember($list, $member)) {
			static::markTestSkipped('Member cannot be created successfully, test is skipped');
		}

		//trying to create another member under the same list and using the same Email address
		$data = static::$memberData;
		$data['email_address'] = $member['email_address'];
		$this->post(sprintf(self::PRE_URI, $list['list_id']), $data);

		$content = \json_decode($this->response->getContent(), true);

		$this->assertResponseStatus(self::HTTP_STATUS_BAD_REQUEST);
		self::assertArrayHasKey('message', $content);
		self::assertEquals(MembersController::getEmailDuplicateError($data['email_address'], $list['list_id']), $content['message']);
	}

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testRemoveMemberNotFoundException(): void
    {
        $this->delete($this->getMemberUri('invalid-list-id', 'invalid-member-id'));

        //firstly list will be validated against list id
        $this->assertListNotFoundResponse('invalid-list-id');
        //$this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns empty successful response when removing existing member.
     *
     * @return void
     */
    public function testRemoveMemberSuccessfully(): void
    {
        if (!$this->createMailchimpListMember($list, $member)) {
			static::markTestSkipped('Member cannot be created successfully, test is skipped');
        }

        $this->delete($this->getMemberUri($list['list_id'], $member['member_id']));

        $this->assertResponseOk();
        self::assertEmpty(\json_decode($this->response->content(), true));
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testShowMemberNotFoundException(): void
    {
        $this->get($this->getMemberUri('invalid-list-id', 'invalid-member-id'));

        //firstly list will be validated against list id
        $this->assertListNotFoundResponse('invalid-list-id');
        //$this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns successful response with member data when requesting existing member.
     *
     * @return void
     */
    public function testShowMemberSuccessfully(): void
    {
        //test for show doesn't need to create list & member on Mailchimp server
        //instead they are created into local test DB, which is created upon each test
        //and is deleted at end of each test;
        $this->createListMember($listId, $memberId);

        $this->get($this->getMemberUri($listId, $memberId));
        $content = \json_decode($this->response->content(), true);

        $this->assertResponseOk();

        self::assertArrayHasKey('member_id', $content);
        self::assertEquals($memberId, $content['member_id']);
        self::assertArrayHasKey('list_id', $content);
        self::assertEquals($listId, $content['list_id']);
        foreach (static::$memberData as $key => $value) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals($value, $content[$key]);
        }
    }

    /**
     * Test application returns error response when member not found.
     *
     * @return void
     */
    public function testUpdateMemberNotFoundException(): void
    {
        $this->put($this->getMemberUri('invalid-list-id', 'invalid-member-id'));

        //firstly list will be validated against list id
        $this->assertListNotFoundResponse('invalid-list-id');
        //$this->assertMemberNotFoundResponse('invalid-member-id');
    }

    /**
     * Test application returns successfully response when updating existing member with updated values.
     *
     * @return void
     */
    public function testUpdateMemberSuccessfully(): void
    {
		if (!$this->createMailchimpListMember($list, $member)) {
			static::markTestSkipped('Member cannot be created successfully, test is skipped');
		}

        //email cannot be updated because it will cause MailchimpID being changed
        $dataNew = [
            'language' => 'NEW' . static::$memberData['language'],
            //the status cannot be arbitrary
            'status' => (static::$memberData['status'] == 'subscribed') ? 'unsubscribed' : 'subscribed',
        ];
        $this->put($this->getMemberUri($list['list_id'], $member['member_id']), $dataNew);
        $content = \json_decode($this->response->content(), true);

        $this->checkAccountBlocked();

        $this->assertResponseOk();

        foreach (static::$memberData as $key => $valOrg) {
            self::assertArrayHasKey($key, $content);
            self::assertEquals($dataNew[$key] ?? $valOrg, $content[$key]);
        }
    }

    /**
     * Test application returns error response with errors when member validation fails.
     *
     * @return void
     */
    public function testUpdateMemberValidationFailed(): void
    {
		if (!$this->createMailchimpListMember($list, $member)) {
			static::markTestSkipped('Member cannot be created successfully, test is skipped');
		}

        $this->put($this->getMemberUri($list['list_id'], $member['member_id']), ['status' => 'TestingInvalidStatus']);

        $this->checkAccountBlocked();

        $content = \json_decode($this->response->content(), true);

        $this->assertResponseStatus(self::HTTP_STATUS_BAD_REQUEST);
        self::assertArrayHasKey('message', $content);
        $message = \json_decode($content['message'], true);
        self::assertArrayHasKey('title', $message);
        self::assertArrayHasKey('status', $message);
        self::assertArrayHasKey('detail', $message);
        self::assertEquals('Invalid Resource', $message['title']);
        self::assertEquals(self::HTTP_STATUS_BAD_REQUEST, $message['status']);
        self::assertEquals('Invalid status given: TestingInvalidStatus', $message['detail']);
    }

}
