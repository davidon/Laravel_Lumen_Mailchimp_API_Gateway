<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Database\Entities\MailChimp\MailChimpMember;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MailChimp\MembersController;
use Mailchimp\Mailchimp;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class MemberTestCase extends WithDatabaseTestCase
{
    protected const PRE_URI = "/mailchimp/lists/%s/members";

    protected const MEMBER_URI = self::PRE_URI . '/%s';

    /**
     * @var array
     */
    protected $createdlistIds = [];

    protected const ERROR_MAILCHIMP = MembersController::ERROR_MAILCHIMP;

    /**
     * @var array
     */
    protected static $memberData = [
        'email_address' => 'RoyaltyCoLtd@hotmail.com',
        //subscribed|unsubscribed|cleaned|pending
        'status' => 'subscribed',
        'language' => 'US English',
        'vip' => true,
        'location' => [
            'latitude' => '-37.898725',
            'longitude' => '145.049333',
        ],
        'ip_signup' => '172.198.34.87',
        'tags' => [
            'Soccer',
            'Fashion'
        ],
    ];

    /**
     * @var array
     */
    protected static $notRequired = [
        'language',
        'vip',
        'location',
        'ip_signup',
        'tags'
    ];

    /**
     * Asserts error response when member not found.
     *
     * @param string $memberId
     *
     * @return void
     */
    protected function assertMemberNotFoundResponse(string $memberId): void
    {
        $content = \json_decode($this->response->content(), true);
        $this->assertResponseStatus(self::HTTP_STATUS_NOT_FOUND);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(Controller::getErrorMessage(MailChimpMember::class, Controller::idsDesc($memberId)), $content['message']);
    }

    /**
     * Create MailChimp member into database.
     *
     * @param array $data
     * @param bool $setMailchimpId Whether set Mailchimp ID
     *
     * @return \App\Database\Entities\MailChimp\MailChimpMember
     */
    protected function createMember(array $data, bool $setMailchimpId = false): MailChimpMember
    {
        $member = new MailChimpMember($data);

        if ($setMailchimpId) {
            //the member created above has no mailchimp ID and will cause failure before making request to Mailchimp API
            $member->setMailChimpId(static::MEMBER_MAILCHIMP_ID);
        }

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * get expected error message
     * @param string $listId
     * @param string $memberId
     * @return string
     */
    protected function getMailChimpResponseError(?string $listId, ?string $memberId = null): string {
        return MembersController::getErrorMessage(MembersController::ERROR_MAILCHIMP, MembersController::idsDesc($listId, $memberId));
    }

    /**
     * Get expected error message when member's email address is changed
     * @param string $orgEmail
     * @param string $newEmail
     * @return string
     */
    protected function getMailChimpResponseEmailChangedError(string $orgEmail, string $newEmail) {
        return MembersController::getErrorMessageEmailChanged($orgEmail, $newEmail);
    }

    /**
     * create list & member, and get list id & member id as pass-by-reference parameters
     *
     * Put output as referenced parameters to avoid return multiple types, and keep type hint effective
     *
     * @param string $listId list id for output
     * @param string $memberId member id for output
     * @param bool $setMemberMailchimpId Whether set member Mailchimp ID
     * @return bool
     */
    protected function createListMember(?string &$listId, ?string &$memberId, bool $setMemberMailchimpId = false): bool
    {
        $list = $this->createList(static::$listData, true);
        // If there is no list id, skip
        if (!$this->validateList($list)) {
            return false;
        }
        $listId = $list->getId();

        $memberData = static::$memberData;
        $memberData['list_id'] = $listId;

        $member = $this->createMember($memberData, $setMemberMailchimpId);
        // If there is no member id, skip
        if (!$this->validateMember($member)) {
            return false;
        }

        $memberId = $member->getId();
        return true;
    }

    /**
     * Generate member URI
     * @param null|string $listId
     * @param null|string $memberId
     * @return string
     */
    protected function getMemberUri(?string $listId, ?string $memberId): string {
        return \sprintf(self::MEMBER_URI, $listId, $memberId);
    }

    /**
     * Generate member URI first part
     * @param null|string $listId
     * @return string
     */
    protected function getPreUri(?string $listId): string {
        return \sprintf(self::PRE_URI, $listId);
    }
}
