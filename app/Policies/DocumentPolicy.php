<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $doc)
    {
        if ($user->hasRole('admin')) return true;
        if ($doc->access_level === 'public') return true;
        if ($doc->access_level === 'department') {
            return $user->department_id === $doc->department_id;
        }
        return $doc->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->hasAnyRole(['admin','manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $doc)
    {
        return $user->hasRole('admin')
            || ($user->hasRole('manager') && $doc->uploaded_by === $user->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $doc)
    {
        return $this->update($user,$doc);
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
