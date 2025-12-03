@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="border mt-1 p-3 shadow rounded bg-white">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="fw-bold text-dark">All Stock Holds</h3>
                            <a href="{{ route('create-stock-hold') }}" class="btn btn-primary">
                                Create Stock Hold
                            </a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-dark text-center">
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Sale ID</th>
                                        <th>Invoice ID</th>
                                        <th>Party Type</th>
                                        <th>Party Name</th>
                                        <th>Warehouse</th>
                                        <th>Product</th>
                                        <th>Item ID</th>
                                        <th>Sale Qty</th>
                                        <th>Hold Qty</th>
                                        <th>Remarks</th>
                                        <th>Meta (source)</th>
                                        <th>Status</th>
                                        <th>Entry Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody class="text-center">
                                    @forelse($holds as $h)
                                    <tr>
                                        <td>{{ $h->id ?? '-' }}</td>

                                        <td>{{ optional($h->entry_date)->format('Y-m-d') ?? '-' }}</td>

                                        {{-- sale_id and invoice id --}}
                                        <td>{{ $h->sale_id ?? '-' }}</td>
                                        <td>{{ $h->invoice_id ?? '-' }}</td>

                                        {{-- party type --}}
                                        <td>{{ $h->party_type ?? '-' }}</td>

                                        {{-- party name: use accessor or relations if available --}}
                                        <td class="text-start">
                                            @php
                                            // prefer accessor party_name, then loaded relations, then fallback to '-'
                                            $partyName = $h->party_name ?? null;
                                            if (! $partyName) {
                                            if ($h->party_type === 'vendor') {
                                            $partyName = $h->partyVendor->name ?? $h->partyVendor->phone ?? null;
                                            } elseif ($h->party_type === 'customer') {
                                            $partyName = $h->partyCustomer->customer_name ?? $h->partyCustomer->mobile ?? null;
                                            }
                                            }
                                            @endphp
                                            {{ $partyName ?? '-' }}
                                        </td>

                                        {{-- warehouse --}}
                                        <td>{{ $h->warehouse->warehouse_name ?? '-' }}</td>

                                        {{-- product --}}
                                        <td class="text-start">{{ $h->product->name ?? ('Product #'.$h->product_id ?? '-') }}</td>

                                        {{-- item id --}}
                                        <td>{{ $h->item_id ?? '-' }}</td>

                                        {{-- sale qty / hold qty --}}
                                        <td>{{ $h->sale_qty !== null ? (string)$h->sale_qty : '-' }}</td>
                                        <td>{{ $h->hold_qty !== null ? (string)$h->hold_qty : '-' }}</td>

                                        {{-- remarks --}}
                                        <td class="text-start">{{ $h->remarks ? e($h->remarks) : '-' }}</td>

                                        {{-- meta: show source if present otherwise full json (pretty) --}}
                                        <td>
                                            @php
                                            $meta = $h->meta ?? null;
                                            $metaSource = null;
                                            if (is_array($meta) || is_object($meta)) {
                                            $metaArr = (array) $meta;
                                            $metaSource = $metaArr['source'] ?? null;
                                            } else {
                                            // if stored as JSON string
                                            try {
                                            $decoded = json_decode($meta, true);
                                            if (is_array($decoded)) {
                                            $metaSource = $decoded['source'] ?? null;
                                            }
                                            } catch (\Throwable $ex) {}
                                            }
                                            @endphp

                                            @if($metaSource)
                                            <span class="badge bg-info text-dark">{{ ucfirst($metaSource) }}</span>
                                            @elseif($meta)
                                            <pre style="white-space:pre-wrap;max-width:200px;margin:0">{{ is_array($meta) ? json_encode($meta, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : (string)$meta }}</pre>
                                            @else
                                            -
                                            @endif
                                        </td>

                                        {{-- status --}}
                                        <td>
                                            @if($h->status == 0)
                                            <span class="badge bg-warning">Hold</span>
                                            @else
                                            <span class="badge bg-success">Released</span>
                                            @endif
                                        </td>

                                        {{-- timestamps --}}
                                        <td>{{ optional($h->created_at)->format('Y-m-d H:i') ?? '-' }}</td>


                                        <td>
                                            @if($h->status == 0)
                                            <a href="{{ route('stock-holds.release', $h->id) }}" class="btn btn-sm btn-primary">Release</a>
                                            @else
                                            <span class="text-muted">Released</span>
                                            @endif
                                        </td>

                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="16" class="text-center text-muted">No Stock Hold Records found</td>
                                    </tr>


                                    @endforelse



                                </tbody>
                            </table>
                        </div> <!-- table-responsive -->

                    </div> <!-- border -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection