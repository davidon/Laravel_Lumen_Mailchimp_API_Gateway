<?php
declare(strict_types=1);

namespace Tests\App\TestCases\MailChimp;

use App\Http\Controllers\MailChimp\ListsController;
use Illuminate\Http\JsonResponse;
use Mailchimp\Mailchimp;
use Mockery;
use Mockery\MockInterface;
use Tests\App\TestCases\WithDatabaseTestCase;

abstract class ListTestCase extends WithDatabaseTestCase
{
    protected const ERROR_MAILCHIMP = ListsController::ERROR_MAILCHIMP;

    /**
     * @var array
     */
    protected $createdListIds = [];

    /**
     * @var array
     */
    protected static $notRequired = [
        'notify_on_subscribe',
        'notify_on_unsubscribe',
        'use_archive_bar',
        'visibility'
    ];

    /**
     * Returns mock of MailChimp to trow exception when requesting their API.
     *
     * @param string $method
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Mockery requires static access to mock()
     */
    protected function mockMailChimpSuccess(string $method): MockInterface
    {
        $mailChimp = Mockery::mock(Mailchimp::class);

        $mailChimp
            ->shouldReceive($method)
            ->once()
            ->withArgs(function (string $method, ?array $options = null) {
                return !empty($method) && (null === $options || \is_array($options));
            })
            ->andReturn(json_encode(static::$listData));

        return $mailChimp;
    }

    /**
     * Asserts error response when MailChimp ID is empty.
     *
     * @param \Illuminate\Http\JsonResponse $response
     * @param string $listId
     *
     * @return void
     */
    protected function assertMailChimpErrorResponse(JsonResponse $response, string $listId): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(self::HTTP_STATUS_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals(static::getMailChimpResponseError($listId), $content['message']);
    }

    /**
     * Asserts success response.
     *
     * @param JsonResponse $response
     *
     * @return void
     */
    protected function assertSuccessResponse(JsonResponse $response): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(self::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($content);
        self::assertArrayHasKey('list_id', $content);
        self::assertArrayHasKey('mail_chimp_id', $content);
        self::assertEquals(static::$listData['name'], $content['name']);
        self::assertEquals(static::$listData['contact'], $content['contact']);
    }

    /**
     * get expected error message for Mailchimp not found
     * @param string $listId
     * @return string
     */
    protected function getMailChimpResponseError(string $listId): string {
        return ListsController::getErrorMessage(ListsController::ERROR_MAILCHIMP, ListsController::idsDesc($listId));
    }
}
