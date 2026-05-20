<?php

namespace App\Filament\Imports;

use App\Models\SalesOrder;
use App\Models\Customer;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class SalesOrderImporter extends Importer
{
    protected static ?string $model = SalesOrder::class;

    protected function beforeValidate(): void
    {
        if (isset($this->data['order_date'])) {
            try {
                if (str_contains($this->data['order_date'], '/')) {
                    $format = str_contains($this->data['order_date'], ':') ? 'd/m/Y H:i:s' : 'd/m/Y';
                    $this->data['order_date'] = Carbon::createFromFormat($format, $this->data['order_date'])->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // Ignore and let validation catch invalid dates
            }
        }

        if (isset($this->data['financial_status'])) {
            $financialStatusMap = [
                'confirmado' => 'pago',
                'pago' => 'pago',
                'aguardando pagamento' => 'aguardando_pagamento',
                'cancelado' => 'cancelado',
            ];
            $key = strtolower(trim($this->data['financial_status']));
            $this->data['financial_status'] = $financialStatusMap[$key] ?? 'aguardando_pagamento';
        }

        if (isset($this->data['dispatch_status'])) {
            $dispatchStatusMap = [
                'não está embalado' => 'preparando',
                'embalado' => 'preparando',
                'pronto para envio' => 'preparando',
                'preparando' => 'preparando',
                'enviado' => 'enviado',
                'entregue' => 'entregue',
            ];
            $key = strtolower(trim($this->data['dispatch_status']));
            $this->data['dispatch_status'] = $dispatchStatusMap[$key] ?? 'preparando';
        }

        $customerName = $this->data['customer'] ?? null;
        if ($customerName) {
            Customer::firstOrCreate(
                ['name' => $customerName],
                [
                    'email' => $this->originalData['E-mail'] ?? null,
                    'phone' => $this->originalData['Telefone'] ?? null,
                    'zip_code' => $this->originalData['Código postal'] ?? null,
                    'address' => trim(($this->originalData['Endereço'] ?? '') . ' ' . ($this->originalData['Número'] ?? '') . ' ' . ($this->originalData['Complemento'] ?? '')),
                    'city' => $this->originalData['Cidade'] ?? null,
                    'state' => $this->originalData['Estado'] ?? null,
                ]
            );
        }
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->label('ID (Nuvemshop)')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('customer')
                ->label('Cliente')
                ->requiredMapping()
                ->rules(['required'])
                ->fillRecordUsing(function (SalesOrder $record, string $state): void {
                    $customer = Customer::where('name', $state)->first();
                    if ($customer) {
                        $record->customer_id = $customer->id;
                    }
                }),
            ImportColumn::make('order_date')
                ->label('Data do Pedido')
                ->requiredMapping()
                ->rules(['required', 'date']),
            ImportColumn::make('financial_status')
                ->label('Status Financeiro')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('dispatch_status')
                ->label('Status de Envio')
                ->requiredMapping()
                ->rules(['required', 'string']),
            ImportColumn::make('tracking_code')
                ->label('Código de Rastreio')
                ->rules(['nullable', 'string', 'max:255']),
            ImportColumn::make('total_amount')
                ->label('Total (R$)')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'numeric']),
            ImportColumn::make('total_cost')
                ->label('Custo Total (R$)')
                ->numeric()
                ->rules(['nullable', 'numeric']),
        ];
    }

    public function resolveRecord(): ?SalesOrder
    {
        return SalesOrder::firstOrNew([
            'id' => $this->data['id'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'A importação de pedidos foi concluída. ' . number_format($import->successful_rows) . ' ' . str('linha')->plural($import->successful_rows) . ' importadas com sucesso.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('linha')->plural($failedRowsCount) . ' falharam na importação.';
        }

        return $body;
    }
}
