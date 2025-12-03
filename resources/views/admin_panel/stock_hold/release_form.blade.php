@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
  <div class="main-content-inner">
    <div class="container-fluid">

      <div class="row p-1">
        <div class="col-lg-10 m-auto">
          <div class="card shadow-sm">

            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="mb-0 fw-bold">Stock Release</h4>
              <a href="{{ route('stock-hold-list') }}" class="btn btn-light">Back to list</a>
            </div>

            <div class="card-body">

              {{-- SUCCESS --}}
              @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
              @endif

              {{-- ERRORS --}}
              @if($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach($errors->all() as $err)
                  <li>{{ $err }}</li>
                  @endforeach
                </ul>
              </div>
              @endif

              <form method="POST"
                action="{{ route('stock-holds.release.store', $hold->id) }}">
                @csrf

                {{-- TOP ROW --}}
                <div class="row mb-3">

                  <div class="col-md-2">
                    <label class="form-label fw-bold">Stock Release#</label>
                    <input class="form-control" name="release_no" value="{{ $releaseNumber }}" readonly>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label fw-bold">Stock Hold#</label>
                    <input class="form-control" value="{{ $hold->id }}" readonly>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label fw-bold">Date</label>
                    <input class="form-control" value="{{ date('Y-m-d') }}" readonly>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label fw-bold">Type</label>
                    <input class="form-control" value="{{ ucfirst($hold->party_type) }}" readonly>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-bold">Warehouse</label>
                    <select name="warehouse_id" class="form-select">
                      <option value="">-- keep current --</option>
                      @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected($w->id == $hold->warehouse_id)>
                          {{ $w->warehouse_name }}
                        </option>
                      @endforeach
                    </select>
                  </div>

                </div>


                {{-- SECOND ROW --}}
                <div class="row mb-3">

                  <div class="col-md-4">
                    <label class="form-label fw-bold">Party (ID)</label>
                    <input class="form-control"
                      value="{{ $hold->party_id }} ({{ ucfirst($hold->party_type) }})"
                      readonly>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-bold">Party Name</label>
                    <input class="form-control"
                      value="
                      @if($hold->party_type == 'customer')
                        {{ $hold->partyCustomer->customer_name ?? '-' }}
                      @elseif($hold->party_type == 'vendor')
                        {{ $hold->partyVendor->name ?? '-' }}
                      @else
                        Walkin Customer
                      @endif
                      " readonly>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-bold">Tel#</label>
                    <input class="form-control"
                      value="{{ $hold->partyCustomer->mobile ?? $hold->partyVendor->phone ?? '-' }}"
                      readonly>
                  </div>

                </div>


                {{-- META + REMARKS --}}
                <div class="row mb-3">
                  <div class="col-md-4">
                    <label class="form-label fw-bold">Meta</label>
                    @php
                      $meta = $hold->meta;
                      $source = is_array($meta) ? ($meta['source'] ?? null) : null;
                    @endphp

                    <input class="form-control"
                      value="{{ $source ? ucfirst($source) : 'N/A' }}"
                      readonly>
                  </div>

                  <div class="col-md-8">
                    <label class="form-label fw-bold">Remarks</label>
                    <input name="remarks" class="form-control" value="{{ $hold->remarks ?? '' }}">
                  </div>
                </div>




                {{-- ITEMS TABLE --}}
                <div class="table-responsive mb-3">
                  <table class="table table-bordered align-middle text-center">
                    <thead class="table-light">
                      <tr>
                        <th>Item ID</th>
                        <th style="text-align:left">Item</th>
                        <th>Sale Qty</th>
                        <th>Release Qty</th>
                      </tr>
                    </thead>

                    <tbody>
                      <tr>
                        <td>{{ $hold->item_id ?? '-' }}</td>
                        <td class="text-start">
                          {{ $hold->product->name ?? 'Product #'.$hold->product_id }}
                        </td>
                        <td>{{ $hold->sale_qty ?? 0 }}</td>
                        <td>
                          <input type="number" name="release_qty"
                            class="form-control text-center release-qty"
                            step="0.01" min="0.01"
                            value="{{ $suggestedQty }}" required>
                        </td>
                      </tr>
                    </tbody>

                    <tfoot>
                      <tr>
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td>
                          <input id="totalRelease" class="form-control text-center" readonly
                            value="{{ $suggestedQty }}">
                        </td>
                      </tr>
                    </tfoot>

                  </table>
                </div>



                {{-- SUBMIT --}}
                <div class="text-end">
                  <button type="submit" class="btn btn-success px-4">
                    Post Release
                  </button>
                </div>

              </form>

            </div> <!-- card-body -->

          </div>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
  function recalc() {
    let t = 0;
    document.querySelectorAll('.release-qty').forEach(e=>{
      t += parseFloat(e.value) || 0;
    });
    document.getElementById('totalRelease').value = t;
  }
  document.addEventListener('input', e=>{
    if (e.target.classList.contains('release-qty')) recalc();
  });
  recalc();
</script>
@endsection
