<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KycResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'kyc_details' => [
                'aadhar_number'    => $this->aadhar_number,


                'pan_number'       => $this->pan_number,


                'bank_name'        => $this->bank_name,
                'bank_account'     => $this->bank_account,
                'ifsc_code'        => $this->ifsc_code,


                'business_name'    => $this->business_name,
                'business_address' => $this->business_address,
                'business_category'    => $this->businessCategory->name,
                'business_category_id' => $this->business_category_id,
                'business_established_date' => date('d-m-Y',strtotime($this->business_established_date)),
                'description'      => $this->description,
                'is_aadhar_verified' => (bool) $this->is_business_verified,
                'is_pan_verified' => (bool) $this->is_business_verified,
                'is_bank_verified' => (bool) $this->is_business_verified,
                'is_business_verified' => (bool) $this->is_business_verified,
                'feedback'     => $this->feedback,
                'documents' => [
                    'aadhar_attachment'  => $this->aadhar_attachment ? asset('storage/' . $this->aadhar_attachment) : null,
                    'pan_attachment'     => $this->pan_attachment ? asset('storage/' . $this->pan_attachment) : null,
                    'bank_attachment'    => $this->bank_attachment ? asset('storage/' . $this->bank_attachment) : null,
                    'business_logo'      => $this->business_logo ? asset('storage/' . $this->business_logo) : null,
                    'identity_document'  => $this->identity_document ? asset('storage/' . $this->identity_document) : null,
                ],
            ],
            'address' => [
                'address' => $this->address,
                'full_address' => $this->full_address,
                'street' => $this->street,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
        ];
    }
}
