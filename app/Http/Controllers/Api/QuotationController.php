<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index(): JsonResponse
    {
        $quotations = Quotation::with(['customer', 'items'])->orderBy('created_at', 'desc')->get();
        return response()->json($quotations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:issue_date',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,sent,accepted,rejected,expired',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            // Calculate totals
            $subtotal = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $tax = $validated['tax'] ?? 0;
            $total = $subtotal + $tax;

            // Create quotation
            $quotation = Quotation::create([
                'customer_id' => $validated['customer_id'],
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => $validated['status'] ?? 'draft',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create quotation items
            foreach ($validated['items'] as $item) {
                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return response()->json($quotation->load(['customer', 'items']), 201);
        });
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $quotation->load(['customer', 'items']);
        return response()->json($quotation);
    }

    public function update(Request $request, Quotation $quotation): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'issue_date' => 'sometimes|required|date',
            'valid_until' => 'sometimes|required|date',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,sent,accepted,rejected,expired',
            'items' => 'sometimes|required|array|min:1',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $quotation) {
            // If items are being updated, recalculate totals
            if (isset($validated['items'])) {
                $subtotal = collect($validated['items'])->sum(function ($item) {
                    return $item['quantity'] * $item['unit_price'];
                });

                $tax = $validated['tax'] ?? $quotation->tax;
                $total = $subtotal + $tax;

                $validated['subtotal'] = $subtotal;
                $validated['total'] = $total;

                // Delete old items and create new ones
                $quotation->items()->delete();
                foreach ($validated['items'] as $item) {
                    QuotationItem::create([
                        'quotation_id' => $quotation->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                    ]);
                }
            }

            $quotation->update($validated);
            return response()->json($quotation->load(['customer', 'items']));
        });
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $quotation->delete();
        return response()->json(['message' => 'Quotation deleted successfully'], 200);
    }
}
