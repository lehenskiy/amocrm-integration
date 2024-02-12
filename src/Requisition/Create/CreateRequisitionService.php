<?php

declare(strict_types=1);

namespace App\Requisition\Create;

use App\Shared\Exception\AmoCrmAuthorizationException;
use App\Shared\Exception\AmoCrmSendApiRequestFailedException;
use App\Shared\Service\AmoCrm\AmoCrmApiClient;
use Psr\Log\LoggerInterface;

class CreateRequisitionService
{
    private const REQUEST_PROTOCOL = 'https';
    private const REQUEST_HOST = 'amocrm.ru';
    private const REQUEST_PATH = '/api/v4/leads/complex';
    private const LEADS_FILLED_FOR_LONG_TIME_FIELD_ID = 166345;
    private const CONTACTS_EMAIL_FIELD_ID = 72785;
    private const CONTACTS_EMAIL_WORK_CATEGORY_ID = 36733;
    private const CONTACTS_PHONE_NUMBER_FIELD_ID = 72783;
    private const CONTACTS_PHONE_NUMBER_WORK_CATEGORY_ID = 36721;

    public function __construct(
        private AmoCrmApiClient $amoCRMApiRequestSender,
        private string $accountName,
        private LoggerInterface $logger,
    ) {
    }

    /**@throws CreateRequisitionFailedException */
    public function createRequisition(RequisitionDTO $requisitionDTO): void
    {
        $url = self::REQUEST_PROTOCOL . '://' . $this->accountName . '.' . self::REQUEST_HOST . self::REQUEST_PATH;
        $requestData = $this->composeRequestData($requisitionDTO);
        $jsonBody = json_encode($requestData);

        try {
            $this->amoCRMApiRequestSender->request(AmoCrmApiClient::POST_REQUEST_METHOD, $url, $jsonBody);
        } catch (AmoCrmAuthorizationException|AmoCrmSendApiRequestFailedException $exception) {
            $this->logger->critical($exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
                'url' => $url,
                'body' => $jsonBody,
                'previous' => $exception->getPrevious(),
                'previous trace' => $exception->getPrevious()?->getTraceAsString(),
                'low level exception' => $exception->getPrevious()?->getPrevious(),
                'low level exception trace' => $exception->getPrevious()?->getPrevious()?->getTraceAsString(),
            ]);
            throw new CreateRequisitionFailedException('Something went wrong, please try later', previous: $exception);
        }
    }

    /**@return array valid AmoCRM API request data */
    private function composeRequestData(RequisitionDTO $requisitionDTO): array
    {
        return
            [
                [
                    'price' => $requisitionDTO->price,
                    'custom_fields_values' => [
                        [
                            'field_id' => self::LEADS_FILLED_FOR_LONG_TIME_FIELD_ID,
                            'values' => [
                                [
                                    'value' => $requisitionDTO->filledForLongTime
                                ]
                            ]
                        ],
                    ],
                    '_embedded' => [
                        'contacts' => [
                            [
                                'name' => $requisitionDTO->name,
                                'custom_fields_values' => [
                                    [
                                        'field_id' => self::CONTACTS_EMAIL_FIELD_ID,
                                        'values' => [
                                            [
                                                'enum_id' => self::CONTACTS_EMAIL_WORK_CATEGORY_ID,
                                                'value' => $requisitionDTO->email,
                                            ]
                                        ]
                                    ],
                                    [
                                        'field_id' => self::CONTACTS_PHONE_NUMBER_FIELD_ID,
                                        'values' => [
                                            [
                                                'enum_id' => self::CONTACTS_PHONE_NUMBER_WORK_CATEGORY_ID,
                                                'value' => $requisitionDTO->phoneNumber,
                                            ]
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ];
    }
}
