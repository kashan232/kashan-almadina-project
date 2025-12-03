@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
  <div class="main-content-inner">
    <div class="container-fluid">

      <div class="row p-1">
        <div class="col-lg-12">
          <div class="border mt-1 p-3 shadow rounded bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h3 class="fw-bold text-dark">All Stock Releases</h3>
              <a href="{{ route('create-stock-hold') }}" class="btn btn-primary">Create Stock Hold</a>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark text-center">
                  <tr>
                    <th>ID</th>
                    <th>Release#</th>
                    <th>Hold#</th>
                    <th>Sale ID</th>
                    <th>Invoice ID</th>
                    <th>Party Type</th>
                    <th>Party ID</th>
                    <th>Party Name</th>
                    <th>Warehouse</th>
                    <th>Product</th>
                    <th>Item ID</th>
                    <th>Sale Qty</th>
                    <th>Release Qty</th>
                    <th>Remarks</th>
                    <th>Meta</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>

                <tbody class="text-center">
                  @forelse($releases as $r)
                    <tr>
                      <td>{{ $r->id }}</td>
                      <td>{{ $r->release_no ?? '-' }}</td>
                      <td>
                        @if($r->hold_id)
                          <a href="{{ route('stock-holds.release', $r->hold_id) }}">{{ $r->hold_id }}</a>
                        @else
                          -
                        @endif
                      </td>
                      <td>{{ $r->sale_id ?? '-' }}</td>
                      <td>{{ $r->invoice_id ?? '-' }}</td>
                      <td>{{ $r->party_type ?? '-' }}</td>
                      <td>{{ $r->party_id ?? '-' }}</td>

                      {{-- party name: try to fetch from hold (if available) or show - --}}
                      <td class="text-start">
                        @php
                          $partyName = null;
                          if ($r->hold && $r->hold->party_type === 'customer' && isset($r->hold->party_id)) {
                              $partyName = optional($r->hold->partyCustomer)->customer_name ?? null;
                          }
                          if (! $partyName && $r->hold && $r->hold->party_type === 'vendor') {
                              $partyName = optional($r->hold->partyVendor)->name ?? null;
                          }
                        @endphp
                        {{ $partyName ?? '-' }}
                      </td>

                      <td>{{ $r->warehouse->warehouse_name ?? '-' }}</td>
                      <td class="text-start">{{ $r->product->name ?? ('Product #' . ($r->product_id ?? '-')) }}</td>
                      <td>{{ $r->item_id ?? '-' }}</td>
                      <td>{{ $r->sale_qty !== null ? (string)$r->sale_qty : '-' }}</td>
                      <td>{{ $r->release_qty !== null ? (string)$r->release_qty : '-' }}</td>
                      <td class="text-start">{{ $r->remarks ? e($r->remarks) : '-' }}</td>

                      {{-- meta pretty --}}
                      <td>
                        @php
                          $meta = $r->meta;
                          $source = null;
                          if (is_array($meta) || is_object($meta)) {
                            $meta = (array)$meta;
                            $source = $meta['from_hold'] ?? null;
                          } else {
                            try { $dec = json_decode($r->meta, true); $source = $dec['from_hold'] ?? null; } catch(\Throwable $ex) {}
                          }
                        @endphp
                        @if($source)
                          <small class="text-muted">from hold #{{ $source }}</small>
                        @else
                          <pre style="white-space:pre-wrap; margin:0; max-width:160px;">{{ is_array($r->meta) ? json_encode($r->meta) : (string)$r->meta }}</pre>
                        @endif
                      </td>

                      <td>{{ optional($r->created_at)->format('Y-m-d H:i') }}</td>

                      <td>
                        <div class="btn-group" role="group">
                          <a href="{{ route('stock-holds.release', $r->hold_id ?? $r->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                          @if($r->hold_id)
                            <a href="{{ route('stock-hold-list') }}#hold-{{ $r->hold_id }}" class="btn btn-sm btn-outline-secondary">Go to Hold</a>
                          @endif
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="17" class="text-center text-muted">No Stock Release records found</td>
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
