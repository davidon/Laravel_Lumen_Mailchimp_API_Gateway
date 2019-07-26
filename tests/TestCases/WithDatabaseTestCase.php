<?php
declare(strict_types=1);

namespace Tests\App\TestCases;

use App\Database\Entities\MailChimp\MailChimpMember;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\JsonResponse;
use App\Database\Entities\MailChimp\MailChimpList;
use App\Http\Controllers\Controller;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;

/**
 * Class WithDatabaseTestCase
 * (Generally methods for members should not come into this class, while many methods for lists should as they are used by members as well)
 * @package Tests\App\TestCases
 */
abstract class WithDatabaseTestCase extends TestCase
{
    protected const MAILCHIMP_EXCEPTION_MESSAGE = 'MailChimp exception';

    /**
     * fake mailchimp ID of list for test
     */
    protected const LIST_MAILCHIMP_ID = '09c44e9d82';

    /**
     * fake mailchimp ID of member for test
     */
    protected const MEMBER_MAILCHIMP_ID = 'cg438cb3fd7e810ed48338d03af0e9d1';

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * list mock data
     * (To be used by both list test and member test)
     *
     * @var array
     */
    protected static $listData = [
        'name' => 'New list',
        'permission_reminder' => 'You signed up for updates on Greeks economy.',
        'email_type_option' => false,
        'contact' => [
            'company' => 'Doe Ltd.',
            'address1' => 'DoeStreet 1',
            'address2' => '',
            'city' => 'Doesy',
            'state' => 'Doedoe',
            'zip' => '1672-12',
            'country' => 'US',
            'phone' => '55533344412'
        ],
        'campaign_defaults' => [
            'from_name' => 'John Doe',
            'from_email' => 'john@doe.com',
            'subject' => 'My new campaign!',
            'language' => 'US'
        ],
        'visibility' => 'prv',
        'use_archive_bar' => false,
        'notify_on_subscribe' => 'notify@loyaltycorp.com.au',
        'notify_on_unsubscribe' => 'notify@loyaltycorp.com.au'
    ];

    /**
     * Create database using doctrine command.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->app->make(Kernel::class)->call('doctrine:schema:create');
        $this->entityManager = $this->app->make(EntityManagerInterface::class);
    }

    /**
     * Drop database using doctrine command.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->app->make(Kernel::class)->call('doctrine:schema:drop', ['--force' => true]);

        parent::tearDown();
    }

    /**
     * Create MailChimp list into database.
     * (To be used by both list test and member test)
     *
     * @param array $data
     * @param bool $setMailchimpId Whether set Mailchimp ID
     *
     * @return MailChimpList
     */
    protected function createList(array $data, bool $setMailchimpId = false): MailChimpList
    {
        $list = new MailChimpList($data);

        if ($setMailchimpId) {
            //the list created above has no mailchimp ID and will cause failure before making request to Mailchimp API
            $list->setMailChimpId(static::LIST_MAILCHIMP_ID);
        }

        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $list;
    }

    /**
     * Validate list
     * @param MailChimpList $list
     * @return bool
     */
    protected function validateList(MailChimpList$list): bool
    {
        return $this->validateId($list->getId());
    }

    /**
     * Validate member
     * @param MailChimpMember $member
     * @return bool
     */
    protected function validateMember(MailChimpMember $member): bool
    {
        return $this->validateId($member->getId());
    }

    /**
     * Validate id
     * @param string $id
     * @return bool
     */
    private function validateId(string $id): bool
    {
        if (is_null($id)) {
            self::markTestSkipped('Unable to continue test, no id provided');
            return false;
        }
        return true;

    }

    /**
     * Asserts error response when MailChimp ID is not found.
     *
     * @param \Illuminate\Http\JsonResponse $response
     * @param string $errorExpected Expected error message
     *
     * @return void
     */
    protected function assertMailChimpNotFoundResponse(JsonResponse $response, string $errorExpected): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(self::HTTP_STATUS_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals($errorExpected, $content['message']);
    }

    /**
     * Asserts error response when MailChimp exception is thrown.
     *
     * @param \Illuminate\Http\JsonResponse $response
     *
     * @return void
     */
    protected function assertMailChimpExceptionResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(self::HTTP_STATUS_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(self::MAILCHIMP_EXCEPTION_MESSAGE, $content['message']);
    }

    /**
     * Asserts error response when list not found.
     * used by both list and member test
     *
     * @param string $listId
     *
     * @return void
     */
    protected function assertListNotFoundResponse(string $listId): void
    {
        $content = \json_decode($this->response->content(), true);

        //testShowListNotFoundException() return 400
        $this->assertResponseStatus(self::HTTP_STATUS_BAD_REQUEST);
        self::assertArrayHasKey('message', $content);
        self::assertEquals(Controller::getErrorMessage(MailChimpList::class, Controller::idsDesc($listId)), $content['message']);
    }

    /**
     * Asserts success response.
     *
     * @param JsonResponse $response
     *
     * @return void
     */
    protected function assertRemoveSuccessResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(self::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($content);
        self::assertEquals([], $content);

    }

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpForException(string $method): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->makePartial()
            ->shouldReceive($method)
            ->once()
            ->withArgs(function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andThrow(new \Exception(self::MAILCHIMP_EXCEPTION_MESSAGE));

        return $mailChimp;
    }

    /**
     * Returns mock of MailChimp to error response when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpForError(string $method): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp->shouldNotReceive($method);

        return $mailChimp;
    }

    /**
     * Returns mock of MailChimp.
     *
     * @return \Mockery\MockInterface
     *
     */
    protected function mockMailChimp(): MockInterface
    {
        return Mockery::mock(Mailchimp::class);
    }

    /**
     * @param array|null $list output response of list
     * @return bool
     */
    protected function createMailchimpList(?array &$list = null)
    {
        $this->post('/mailchimp/lists', static::$listData);
        $list = \json_decode($this->response->getContent(), true);
        $this->checkAccountBlocked();

        if (empty($list['list_id']) || empty($list['mail_chimp_id']))
        {
            //not created list successfully, skip test
            static::markTestSkipped('List cannot be created successfully, test is skipped');
            return false;
        }

    }

    /**
     * check if account is blocked by Mailchimp server
     */
    protected function checkAccountBlocked()
    {
        if ($this->response->getStatusCode() == self::HTTP_STATUS_METHOD_NOT_ALLOWED) {
            //skip if account is blocked by Mailchimp server
            static::markTestSkipped('Test skipped as account is blocked by Mailchimp server');
        }

    }

}
