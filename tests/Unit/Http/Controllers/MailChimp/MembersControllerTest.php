<?php
declare(strict_types=1);

namespace Tests\App\Unit\Http\Controllers\MailChimp;

use App\Http\Controllers\MailChimp\MembersController;
use Tests\App\TestCases\MailChimp\MemberTestCase;
use Illuminate\Http\JsonResponse;

class MembersControllerTest extends MemberTestCase
{
    /**
     * Asserts error response when member's Email address is changed.
     * (Member's email is not allowed to change during update because it will cause member Mailchimp ID changed)
     *
     * @param \Illuminate\Http\JsonResponse $response
     * @param string $errorExpected Expected error message
     *
     * @return void
     */
    protected function assertMailChimpEmailChangeDisallowedResponse(JsonResponse $response, string $errorExpected): void
    {
        $content = \json_decode($response->content(), true);

        self::assertEquals(self::HTTP_STATUS_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('message', $content);
        self::assertEquals($errorExpected, $content['message']);
    }

    /**
     * Test controller returns error response when exception is thrown during create MailChimp request.
     *
     * @return void
     */
    public function testCreateMemberMailChimpException(): void
    {
        $list = $this->createList(static::$listData, true);
        // If there is no list id, skip
        if (!$this->validateList($list)) {
            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('post'));
        $this->assertMailChimpExceptionResponse($controller->create($this->getRequest(static::$memberData), $list->getId()));
    }

    /**
     * Test controller returns error 404 response when making create MailChimp request without providing Mailchimp ID in member.
     *
     * @return void
     */
    public function testCreateMemberMailChimpNotFound(): void
    {
        $list = $this->createList(static::$listData);
        if (!$this->validateList($list)) {
            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimp());

        //putting long expression as param is error prone
        $response = $controller->create($this->getRequest(static::$memberData), $list->getId());
        $errorExpected = $this->getMailChimpResponseError($list->getId());
        $this->assertMailChimpNotFoundResponse($response, $errorExpected);
    }

    /**
     * Test controller returns error response when exception is thrown during remove MailChimp request.
     *
     * @return void
     */
    public function testRemoveMemberMailChimpException(): void
    {
        // If there is no list id or member id, skip
        if (!$this->createListMember($listId,$memberId, true))
        {
            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimpForException('delete'));
        $this->assertMailChimpExceptionResponse($controller->remove($listId, $memberId));
    }

    /**
     * Test controller returns error 404 response when making remove MailChimp request without providing Mailchimp ID.
     *
     * @return void
     */
    public function testRemoveMemberMailChimpNotFound(): void
    {
        // If there is no list id or member id, skip
        if (!$this->createListMember($listId,$memberId, false))
        {
            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimp());
        $this->assertMailChimpNotFoundResponse($controller->remove($listId, $memberId), $this->getMailChimpResponseError($listId,$memberId));
    }

    /**
     * Test controller returns error response when exception is thrown during update MailChimp request.
     *
     * @return void
     */
    public function testUpdateMemberMailChimpException(): void
    {
        // If there is no list id or member id, skip
        if (!$this->createListMember($listId,$memberId, true))
        {
            return;
        }

        $response = $this->getUpdateResponse($this->mockMailChimpForException('patch'), $listId, $memberId);

        $this->assertMailChimpExceptionResponse($response);
    }

    /**
     * Make member update request and get response
     * @param \Mockery\MockInterface $mailchimpMocker
     * @param string $listId
     * @param string $memberId
     * @return JsonResponse
     */
    private function getUpdateResponse(\Mockery\MockInterface $mailchimpMocker, string $listId, string $memberId) : JsonResponse
    {
        /** @noinspection PhpParamsInspection Mock given on purpose - passing MockInterface, but Mailchimp\Mailchimp required */
        $controller = new MembersController($this->entityManager, $mailchimpMocker);

        $memberData = static::$memberData;
        $memberData['status'] .= ' NEW';
        $memberData['language'] .= ' NEW';
        //putting long expression as param is error prone
        return $controller->update($this->getRequest($memberData), $listId, $memberId);
    }

    /**
     * Test controller returns NOT FOUND response when making member update to MailChimp server
     * without providing valid Mailchimp member ID.
     *
     * @return void
     */
    public function testUpdateMemberMailChimpNotFound(): void
    {
        // If there is no list id or member id, skip
        if (!$this->createListMember($listId,$memberId, false))
        {
            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose - passing MockInterface, but Mailchimp\Mailchimp required */
        $response = $this->getUpdateResponse($this->mockMailChimp(), $listId, $memberId);
        $expectedError = $this->getMailChimpResponseError($listId, $memberId);
        $this->assertMailChimpNotFoundResponse($response, $expectedError);
    }

    /**
     * Test controller returns EMAIL CHANGE DISALLOWED response when making member update to MailChimp server
     * with member's email address is different.
     *
     * @return void
     */
    public function testUpdateMemberMailChimpEmailChangeDisallowed(): void
    {
        // If there is no list id or member id, skip
        if (!$this->createListMember($listId,$memberId, true))
        {
            return;
        }

        /** @noinspection PhpParamsInspection Mock given on purpose */
        $controller = new MembersController($this->entityManager, $this->mockMailChimp());

        $memberData = static::$memberData;
        $orgEmail = strtolower($memberData['email_address']);
        $memberData['email_address'] = $newEmail = strtolower('NEW_' . $orgEmail);
        //putting long expression as param is error prone
        $jsonResponse = $controller->update($this->getRequest($memberData), $listId, $memberId);
        $expectedError = $this->getMailChimpResponseEmailChangedError($orgEmail, $newEmail);
        $this->assertMailChimpEmailChangeDisallowedResponse($jsonResponse, $expectedError);
    }
}
