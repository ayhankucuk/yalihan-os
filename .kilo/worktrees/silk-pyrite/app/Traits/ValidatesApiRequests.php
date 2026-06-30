<?php

namespace App\Traits;

use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * ValidatesApiRequests Trait
 *
 * Context7: Standardized API request validation
 * Provides consistent validation and error handling for API controllers
 *
 * Usage:
 * ```php
 * class ExampleController extends Controller
 * {
 *     use ValidatesApiRequests;
 *
 *     public function store(Request $request)
 *     {
 *         $validated = $this->validateRequest($request, [
 *             'name' => 'required|string|max:255',
 *             'email' => 'required|email',
 *         ]);
 *
 *         // Use $validated data...
 *     }
 * }
 * ```
 */
trait ValidatesApiRequests
{
    /**
     * Validate request and return validated data
     *
     * @param  array  $rules  Validation rules
     * @param  array  $messages  Custom validation messages
     * @param  array  $customAttributes  Custom attribute names
     * @return array Validated data
     *
     * @throws ValidationException
     */
    protected function validateRequest(
        Request $request,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): array {
        $validator = Validator::make(
            $request->all(),
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate request and return JSON error response if validation fails
     *
     * @param  array  $rules  Validation rules
     * @param  array  $messages  Custom validation messages
     * @param  array  $customAttributes  Custom attribute names
     * @return array|null Validated data or null if validation fails (response already sent)
     */
    protected function validateRequestOrFail(
        Request $request,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): ?array {
        $validator = Validator::make(
            $request->all(),
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            return null;
        }

        return $validator->validated();
    }

    /**
     * Validate request and return ResponseService validation error if fails
     *
     * @param  array  $rules  Validation rules
     * @param  array  $messages  Custom validation messages
     * @param  array  $customAttributes  Custom attribute names
     * @return array|JsonResponse Validated data or JsonResponse if validation fails
     */
    protected function validateRequestWithResponse(
        Request $request,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        $validator = Validator::make(
            $request->all(),
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            return ResponseService::validationError(
                $validator->errors()->toArray(),
                'Validation failed'
            );
        }

        return $validator->validated();
    }

    /**
     * Validate request with flexible rules (supports both old and new formats)
     *
     * @param  array  $rules  Validation rules
     * @param  array  $alternatives  Alternative field names (e.g., ['category' => 'kategori'])
     * @return array Validated data with normalized field names
     */
    protected function validateRequestFlexible(
        Request $request,
        array $rules,
        array $alternatives = []
    ): array {
        $data = $request->all();

        // Normalize alternative field names
        foreach ($alternatives as $primary => $alternatives) {
            if (! isset($data[$primary])) {
                foreach ((array) $alternatives as $alt) {
                    if (isset($data[$alt])) {
                        $data[$primary] = $data[$alt];
                        break;
                    }
                }
            }
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
