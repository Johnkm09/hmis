<?php

namespace App\Policies\Payment;

use App\Models\Payment\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view invoices');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('view invoices');
    }

    public function create(User $user): bool
    {
        return $user->can('create invoices');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('update invoices');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
