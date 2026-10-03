<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\AssignUserRoleRequest;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserRoleController extends Controller
{
    use AuthorizesRequests;

    public function update(AssignUserRoleRequest $request, User $user)
    {
        $this->authorize('assignRole', $user);

        $newRole = $request->validated('role');

        if (
            $request->user()->hasRole('manager') &&
            $newRole === 'super_admin'
        ) {
            abort(403, 'Managers cannot assign the super_admin role.');
        }

        $user->syncRoles($newRole);

        return ApiResponse::success(
            [
                'user_id' => $user->id,
                'role' => $user->getRoleNames()->first(),
            ],
            'User role updated successfully.'
        );
    }
}
