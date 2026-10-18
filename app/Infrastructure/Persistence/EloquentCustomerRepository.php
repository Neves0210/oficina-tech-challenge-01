<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Customers\CustomerRepository;

class EloquentCustomerRepository extends EloquentRecordRepository implements CustomerRepository
{
    public function __construct()
    {
        parent::__construct(new Customer);
    }
}
