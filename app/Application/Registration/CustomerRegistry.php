<?php

namespace App\Application\Registration;

use App\Domain\Customers\CustomerRepository;
use App\Domain\Customers\DocumentNumber;

class CustomerRegistry extends Registry
{
    public function __construct(CustomerRepository $records)
    {
        parent::__construct($records);
    }

    protected function normalize(array $data): array
    {
        if (isset($data['document'])) {
            $document = new DocumentNumber($data['document']);
            $data['document'] = $document->value;
            $data['document_type'] = $document->type;
        }

        return $data;
    }
}
