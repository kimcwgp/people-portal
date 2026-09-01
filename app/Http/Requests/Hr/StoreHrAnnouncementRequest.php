<?php

namespace App\Http\Requests\Hr;

use Illuminate\Foundation\Http\FormRequest;

class StoreHrAnnouncementRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ];
    }

    public function messages()
    {
        return [
            'image.required' => 'Please upload an announcement image.',
            'image.max' => 'Image size must be less than 2MB.',
        ];
    }

    protected function prepareForValidation()
    {
        if (empty($this->title)) {
            $this->merge(['title' => 'Company Update']);
        }
    }
}