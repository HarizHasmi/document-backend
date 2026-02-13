<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'employee']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $doc): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($doc->access_level === 'public') {
            return true;
        }

        if ($doc->uploaded_by === $user->id) {
            return true;
        }

        if (
            $user->hasRole('manager') &&
            $doc->access_level === 'department' &&
            $user->department_id === $doc->department_id
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $doc): bool
    {
        return $user->hasRole('admin')
            || ($user->hasRole('manager') && $doc->uploaded_by === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $doc): bool
    {
        return $this->update($user, $doc);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
