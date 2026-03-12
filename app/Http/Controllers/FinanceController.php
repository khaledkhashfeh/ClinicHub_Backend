<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicDoctor;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    /**
     * GET /api/v1/finance/summary
     * Query: month, year, clinic_id (for doctor/secretary)
     */
    public function summary(Request $request): JsonResponse
    {
        $clinicId = $this->resolveClinicId($request);

        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], [
            'month.required' => 'الشهر مطلوب (1-12).',
            'year.required' => 'السنة مطلوبة.',
            'month.min' => 'الشهر يجب أن يكون بين 1 و 12.',
            'month.max' => 'الشهر يجب أن يكون بين 1 و 12.',
            'year.min' => 'السنة غير صالحة.',
            'year.max' => 'السنة غير صالحة.',
        ]);

        $month = (int) $validated['month'];
        $year = (int) $validated['year'];

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        // Total expenses (stored as positive numbers)
        $totalExpenses = (float) Expense::where('clinic_id', $clinicId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        // Total revenue from completed appointments for this clinic
        $totalRevenue = (float) Appointment::where('clinic_id', $clinicId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', Appointment::STATUS_COMPLETED)
            ->sum('price_at_booking');

        $netSavings = $totalRevenue - $totalExpenses;

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_savings' => $netSavings,
                'currency' => 'ليرة سورية',
            ],
        ]);
    }

    /**
     * GET /api/v1/finance/expenses
     * Query: month, year, page, limit, clinic_id (for doctor/secretary)
     */
    public function expenses(Request $request): JsonResponse
    {
        $clinicId = $this->resolveClinicId($request);

        $validated = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'month.required' => 'الشهر مطلوب (1-12).',
            'year.required' => 'السنة مطلوبة.',
        ]);

        $month = (int) $validated['month'];
        $year = (int) $validated['year'];
        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 20);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $query = Expense::with('category')
            ->where('clinic_id', $clinicId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        $items = $query
            ->forPage($page, $limit)
            ->get();

        $data = $items->map(function (Expense $expense) {
            return [
                'id' => $expense->id,
                'title' => $expense->title,
                // Return as negative to reflect expense in UI
                'amount' => -1 * (float) $expense->amount,
                'date' => $expense->date->toDateString(),
                'category' => $expense->category?->name,
                'icon_type' => $expense->category?->icon_type,
            ];
        })->all();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * POST /api/v1/finance/expenses
     * Body: title, amount, date, category_id, notes?
     */
    public function storeExpense(Request $request): JsonResponse
    {
        $clinicId = $this->resolveClinicId($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'category_id' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $category = ExpenseCategory::where('id', $validated['category_id'])
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود أو لا يتبع لهذه العيادة.',
            ], 422);
        }

        $expense = Expense::create([
            'clinic_id' => $clinicId,
            'category_id' => $category->id,
            'title' => $validated['title'],
            // Store as positive; UI can display negative
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $expense,
        ], 201);
    }

    /**
     * DELETE /api/v1/finance/expenses/{expense_id}
     */
    public function deleteExpense(Request $request, int $expenseId): JsonResponse
    {
        $clinicId = $this->resolveClinicId($request);

        $expense = Expense::where('id', $expenseId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$expense) {
            return response()->json([
                'success' => false,
                'message' => 'المصروف غير موجود أو لا يتبع لهذه العيادة.',
            ], 404);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }

    /**
     * GET /api/v1/finance/categories
     */
    public function categories(Request $request): JsonResponse
    {
        $clinicId = $this->resolveClinicId($request);

        $categories = ExpenseCategory::where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'name', 'icon_type']);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * POST /api/v1/finance/categories
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $clinicId = $this->resolveClinicId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon_type' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'اسم التصنيف مطلوب.',
        ]);

        $category = ExpenseCategory::create([
            'clinic_id' => $clinicId,
            'name' => $validated['name'],
            'icon_type' => $validated['icon_type'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $category,
        ], 201);
    }

    /**
     * PATCH /api/v1/finance/categories/{category_id}
     */
    public function updateCategory(Request $request, int $categoryId): JsonResponse
    {
        $clinicId = $this->resolveClinicId($request);

        $category = ExpenseCategory::where('id', $categoryId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود أو لا يتبع لهذه العيادة.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'icon_type' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $category->fill($validated);
        $category->save();

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    /**
     * DELETE /api/v1/finance/categories/{category_id}
     */
    public function deleteCategory(Request $request, int $categoryId): JsonResponse
    {
        $clinicId = $this->resolveClinicId($request);

        $category = ExpenseCategory::where('id', $categoryId)
            ->where('clinic_id', $clinicId)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'التصنيف غير موجود أو لا يتبع لهذه العيادة.',
            ], 404);
        }

        if ($category->expenses()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف تصنيف مرتبط بمصاريف. انقل المصاريف أولاً أو احذفها.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * Resolve clinic_id from the authenticated token and optional clinic_id param.
     *
     * - If authenticated as clinic (guard: clinic): use clinic id.
     * - If authenticated as doctor (guard: api): require clinic_id and validate association via clinic_doctor.
     * - If authenticated as secretary (guard: api):
     *      - If secretary of a clinic: use that clinic.
     *      - If secretary of a doctor: require clinic_id and validate association.
     *      - If secretary of a medical center: require clinic_id and validate that clinic belongs to that center.
     */
    private function resolveClinicId(Request $request): int
    {
        // If authenticated as clinic
        $clinic = Auth::guard('clinic')->user();
        if ($clinic instanceof Clinic) {
            $requestedClinicId = $request->query('clinic_id');
            if ($requestedClinicId && (int) $requestedClinicId !== (int) $clinic->id) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to access another clinic data.',
                ], 403));
            }

            return (int) $clinic->id;
        }

        // Authenticated as user (doctor / secretary / others) via api guard
        $user = Auth::guard('api')->user();
        if (!$user) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401));
        }

        $clinicIdParam = $request->query('clinic_id');

        // If user is a doctor
        if ($user->doctor) {
            if (!$clinicIdParam || (int) $clinicIdParam < 1) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'clinic_id is required for doctor and must be a valid id.',
                ], 422));
            }

            $clinicId = (int) $clinicIdParam;

            $exists = ClinicDoctor::where('clinic_id', $clinicId)
                ->where('doctor_id', $user->doctor->id)
                ->exists();

            if (!$exists) {
                abort(response()->json([
                    'success' => false,
                    'message' => 'Doctor is not associated with this clinic.',
                ], 403));
            }

            return $clinicId;
        }

        // If user is a secretary
        $secretary = $user->secretary;
        if ($secretary) {
            // Secretary linked directly to a clinic
            if ($secretary->entity_type === Clinic::class) {
                $clinicId = (int) $secretary->entity_id;

                if ($clinicIdParam && (int) $clinicIdParam !== $clinicId) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'You are not allowed to access another clinic data.',
                    ], 403));
                }

                return $clinicId;
            }

            // Secretary linked to a doctor
            if ($secretary->entity_type === \App\Models\Doctor::class) {
                if (!$clinicIdParam || (int) $clinicIdParam < 1) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'clinic_id is required for secretary of doctor and must be a valid id.',
                    ], 422));
                }

                $clinicId = (int) $clinicIdParam;

                $exists = ClinicDoctor::where('clinic_id', $clinicId)
                    ->where('doctor_id', $secretary->entity_id)
                    ->exists();

                if (!$exists) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Doctor is not associated with this clinic.',
                    ], 403));
                }

                return $clinicId;
            }

            // Secretary linked to a medical center
            if ($secretary->entity_type === \App\Models\MedicalCenter::class) {
                if (!$clinicIdParam || (int) $clinicIdParam < 1) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'clinic_id is required for medical center secretary and must be a valid id.',
                    ], 422));
                }

                $clinicId = (int) $clinicIdParam;

                $clinic = Clinic::where('id', $clinicId)
                    ->where('medical_center_id', $secretary->entity_id)
                    ->first();

                if (!$clinic) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Clinic does not belong to this medical center.',
                    ], 403));
                }

                return $clinicId;
            }
        }

        // Any other user type (e.g. patient) is not allowed
        abort(response()->json([
            'success' => false,
            'message' => 'You are not allowed to access clinic finance data.',
        ], 403));
    }
}

