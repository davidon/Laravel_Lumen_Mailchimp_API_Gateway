<?php
declare(strict_types=1);

namespace App\Http\Controllers\MailChimp;

use App\Database\Entities\MailChimp\MailChimpList;
use App\Http\Controllers\Controller;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mailchimp\Mailchimp;

class ListsController extends Controller
{
    /**
     * @var \Mailchimp\Mailchimp
     */
    private $mailChimp;

    /**
     * Data from Mailchimp server such as ID could be appended
     * e.g. Mailchimp ID of App\Database\Entities\MailChimp\MailChimpList
     */
    const ERROR_MAILCHIMP = '['. MailChimpList::class . '] ' . parent::ERR_MAILCHIMP_ID;

    /**
     * ListsController constructor.
     *
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Mailchimp\Mailchimp $mailchimp
     */
    public function __construct(EntityManagerInterface $entityManager, Mailchimp $mailchimp)
    {
        parent::__construct($entityManager);

        $this->mailChimp = $mailchimp;
    }

    /**
     * Create MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // Instantiate entity
        $list = new MailChimpList($request->all());
        // Validate entity
        $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        try {
            // Save list into db
            $this->saveEntity($list);
            // Save list into MailChimp
            $response = $this->mailChimp->post('lists', $list->toMailChimpArray());
            // Set MailChimp id on the list and save list into db
            $this->saveEntity($list->setMailChimpId($response->get('id')));
        } catch (Exception $exception) {
            // Return error response if something goes wrong
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($list->toArray());
    }

    /**
     * Remove MailChimp list.
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(string $listId): JsonResponse
    {
        /** @var MailChimpList|null $list */
        $list = $this->getListbyId($listId);
        if (is_null($list)) {
            return $this->errorList($listId);
        }

        try {
            // Remove list from database
            $this->removeEntity($list);
            // Remove list from MailChimp
            $mailchimpId = $list->getMailChimpId();
            //it's possible not being saved to Mailchimp server;
            // If you know it will definitely fail, why still send request?
            if (empty($mailchimpId)) {
                return $this->errorMailChimp($listId);
            }
            $this->mailChimp->delete(\sprintf('lists/%s', $mailchimpId));
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse([]);
    }

    /**
     * Retrieve and return MailChimp list.
     *
     * The list data is actually retrieved from DB as it's stored/updated on creating or updating,
     * and so no need to issue API call to Mailchimp
     *
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $listId): JsonResponse
    {
        return $this->getListResponse($this->getListbyId($listId), $listId);
    }

    /**
     * Update MailChimp list.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $listId
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $listId): JsonResponse
    {
        /** @var MailChimpList|null $list */
        $list = $this->getListbyId($listId);
        if (is_null($list)) {
            return $this->errorList($listId);
        }

        // Update list properties
        $list->fill($request->all());

        // Validate entity
        $validator = $this->getValidationFactory()->make($list->toMailChimpArray(), $list->getValidationRules());

        if ($validator->fails()) {
            // Return error response if validation failed
            return $this->errorResponse([
                'message' => 'Invalid data given',
                'errors' => $validator->errors()->toArray()
            ]);
        }

        $mailchimpId = $list->getMailChimpId();

        //it's possible not being saved to Mailchimp server;
        // If you know it will definitely fail, why still send request?
        if (empty($mailchimpId)) {
            return $this->errorMailChimp($listId);
        }

        try {
            // Update list into database
            $this->saveEntity($list);
            // Update list into MailChimp
            $this->mailChimp->patch(\sprintf('lists/%s', $mailchimpId), $list->toMailChimpArray());
        } catch (Exception $exception) {
            return $this->errorResponse(['message' => $exception->getMessage()]);
        }

        return $this->successfulResponse($list->toArray());
    }

    /**
     * Retrieve and return MailChimp lists.
     *
     * The list data is actually retrieved from DB as it's stored/updated on creating or updating,
     * and so no need to issue API call to Mailchimp
     *
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showall(): JsonResponse
    {
        return $this->getListsResponse($this->getLists());
    }
}
