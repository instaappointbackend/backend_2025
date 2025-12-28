<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamMemberRequest;
use App\Http\Resources\TeamMemberResponse;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeamMemberController extends Controller
{
    use ApiResponseTrait;

    /**
     * List team members for the authenticated vendor.
     * Only vendors (users with vendor_id == null) can manage team members.
     */
    public function index()
    {
        $vendor = Auth::user();


        $teamMembers = User::where('vendor_id', $vendor->id)->get();
        return $this->success(TeamMemberResponse::collection($teamMembers), 'Members retrieved successfully.');
    }

    /**
     * Store a new team member.
     */
    public function store(TeamMemberRequest $request)
    {
        $vendor = Auth::user();




        $data = $request->only(['name', 'email', 'mobile',  'gender', 'dob', 'address', 'full_address', 'street', 'city', 'state', 'country', 'postal_code', 'latitude', 'longitude']);

        $data['vendor_id']  = $vendor->id;
        $data['role']       = 'vendor_team';
        $data['business_category_id']  = $vendor->business_category_id;
        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $data['profile_picture'] = $path;
        }
        // Generate a unique referral code if not already set
        if (empty($request->has('referral_code'))) {
            $data['referral_code'] = $this->generateUniqueReferralCode();
        }

        $teamMember = User::create($data);

        return $this->success(new TeamMemberResponse($teamMember), 'Team member created successfully.', 201);
    }

    /**
     * Update an existing team member.
     */
    public function update(TeamMemberRequest $request, $id)
    {
        $loggedInUser = Auth::user();

        if (is_null($loggedInUser->vendor_id)) {
            $teamMember = User::where('id', $id)->where('vendor_id', $loggedInUser->id)->first();
        } else {
            if ($loggedInUser->id != $id) {
                return $this->error([], 'Access denied.', 403);
            }
            $teamMember = $loggedInUser;
        }

        if (!$teamMember) {
            return $this->error([], 'Team member not found.', 404);
        }



        $data = $request->only(['name', 'email', 'mobile',  'gender', 'dob', 'address', 'full_address', 'street', 'city', 'state', 'country', 'postal_code', 'latitude', 'longitude']);
        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');

            $data['profile_picture'] = $path;
        }
        $data['business_category_id']  = $loggedInUser->business_category_id;
        $teamMember->update($data);

        return $this->success(new TeamMemberResponse($teamMember), 'Team member updated successfully.');
    }

    /**
     * Delete a team member.
     */
    public function destroy($id)
    {
        $vendor = Auth::user();

        if (!is_null($vendor->vendor_id)) {
            return $this->error([], 'Access denied.', 403);
        }

        $teamMember = User::where('id', $id)->where('vendor_id', $vendor->id)->first();

        if (!$teamMember) {
            return $this->error([], 'Team member not found.', 404);
        }

        $teamMember->delete();

        return $this->success([], 'Team member deleted successfully.');
    }

    /**
     * Generate a unique referral code.
     */
    private function generateUniqueReferralCode()
    {
        do {
            $code = Str::upper(Str::random(8)); // Generate an 8-character uppercase string
        } while (User::where('reference_code', $code)->exists());

        return $code;
    }
}
