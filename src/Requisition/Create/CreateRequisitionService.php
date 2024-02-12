<?php

declare(strict_types=1);

namespace App\Requisition\Create;

use App\Shared\Exception\AmoCrmAuthorizationException;
use App\Shared\Exception\AmoCrmSendApiRequestFailedException;
use App\Shared\Service\AmoCrm\AmoCrmApiRequestSender;
use Psr\Log\LoggerInterface;

class CreateRequisitionService
{
    private const REQUEST_PROTOCOL = 'https';
    private const REQUEST_HOST = 'amocrm.ru';
    private const REQUEST_PATH = '/api/v4/leads/complex';

    public function __construct(
        private AmoCrmApiRequestSender $amoCRMApiRequestSender,
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
            $this->amoCRMApiRequestSender->sendAPIRequest(AmoCrmApiRequestSender::POST_REQUEST_METHOD, $url, $jsonBody);
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
                            'field_id' => 166345,
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
                                        'field_id' => 72785,
                                        'values' => [
                                            [
                                                'enum_id' => 36733,
                                                'value' => $requisitionDTO->email,
                                            ]
                                        ]
                                    ],
                                    [
                                        'field_id' => 72783,
                                        'values' => [
                                            [
                                                'enum_id' => 36721,
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
