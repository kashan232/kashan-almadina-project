@extends('admin_panel.layout.app')
@section('content')

<div class="main-content">
  <div class="main-content-inner">
    <div class="container-fluid">

      <div class="row p-1">
        <div class="col-lg-12 col-md-12 mb-30">
          <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="mb-0 fw-bold">Stock Hold</h4>
            </div>

            <div class="card-body">
              <form id="stockHoldForm" action="{{ route('stock-holds.store') }}" method="POST">
                @csrf

                {{-- TOP: left = basic fields, right = customer info --}}
                <div class="row g-3 mb-3">
                  <div class="col-lg-8">
                    <div class="row g-3">
                      <div class="col-md-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="entry_date" class="form-control"
                          value="{{ old('entry_date', date('Y-m-d')) }}">
                      </div>

                      <div class="col-md-3">
                        <label class="form-label fw-semibold">Type</label>
                        <select id="vendor_type" name="vendor_type" class="form-select">
                          <option disabled selected>Select Type</option>
                          <option value="vendor">Vendor</option>
                          <option value="customer">Customer</option>
                          <option value="walkin">Walkin Customer</option>
                        </select>
                      </div>

                      <div class="col-md-3">
                        <label class="form-label fw-semibold">Select Party</label>
                        <select id="vendor_id" name="vendor_id" class="form-select">
                          <option disabled selected>Select Party</option>
                        </select>
                      </div>

                      <div class="col-md-3">
                        <label class="form-label fw-semibold">Invoice No</label>
                        <select id="invoice_id" name="invoice_id" class="form-select">
                          <option selected disabled>Select Invoice</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="col-lg-2">
                    <div class="row g-3">
                      <div class="col-md-12">
                        <label class="form-label fw-semibold">Hold Type</label>
                        <select name="hold_type" class="form-select">
                          <option selected disabled>Select</option>
                          <option value="hold">Hold</option>
                          <option value="claim">Claim</option>
                        </select>
                      </div>
                      
                    </div>
                  </div>

                  <div class="col-lg-2">
                     <label class="form-label fw-semibold">Select Warehouse</label>
                        <select id="warehouse_id" name="warehouse_id" class="form-select">
                          <option disabled selected>Select Warehouse</option>
                          @foreach($Warehouses as $Warehouse)
                          <option value="{{ $Warehouse->id }}"> {{ $Warehouse->warehouse_name }} </option>
                          @endforeach
                        </select>
                  </div>
                </div>

                {{-- CUSTOMER INFO row --}}
                <div class="row g-3 mb-4">
                  <div class="col-md-5">
                    <label class="form-label fw-semibold">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control"
                      placeholder="Auto-filled after selection" readonly>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label fw-semibold">Tel</label>
                    <input type="text" id="tel" name="tel" class="form-control"
                      placeholder="Auto-filled after selection" readonly>
                  </div>
  <div class="col-md-3">
                        <label class="form-label fw-semibold">Remarks</label>
                        <input type="text" id="remarks" name="remarks" class="form-control"
                          placeholder="Enter remarks (optional)">
                      </div>
                </div>

                {{-- MANUAL ADD: placed above the table for better UX --}}
                <div class="border rounded p-3 mb-4 bg-light">
                  <div class="row g-2 align-items-end">
                    <div class="col-lg-6">
                      <label class="form-label fw-semibold mb-1">Search Product (manual add)</label>
                      <input type="text" id="product_search" class="form-control" placeholder="Type product name or id...">
                      <div id="product_suggestions" class="list-group mt-1" style="display:none; max-height:220px; overflow:auto;"></div>
                    </div>

                    <div class="col-lg-2">
                      <label class="form-label fw-semibold">Available Stock</label>
                      <input type="text" id="manual_stock" class="form-control" readonly placeholder="—">
                    </div>

                    <div class="col-lg-2">
                      <label class="form-label fw-semibold">Hold Quantity</label>
                      <input type="number" id="manual_hold_qty" min="0" class="form-control" value="1">
                    </div>

                    <div class="col-lg-2 text-end">
                      <button type="button" id="btn_add_manual" class="btn btn-outline-primary w-100">Add</button>
                    </div>
                  </div>

                  <small class="text-muted mt-2 d-block">
                    Manual holds are independent of any sale/invoice. Sale quantity is not used for manual rows.
                  </small>
                </div>

                {{-- Table for Hold Items --}}
                <div class="table-responsive mb-3">
                  <table class="table table-bordered align-middle text-center">
                    <thead class="table-light">
                      <tr>
                        <th style="width:8%">Item ID</th>
                        <th style="width:52%; text-align:left">Item Name</th>
                        <th style="width:20%">Sale Quantity</th> <!-- readonly (if invoice) -->
                        <th style="width:20%">Hold Quantity</th> <!-- editable -->
                      </tr>
                    </thead>
                    <tbody id="itemTableBody">
                      <!-- rows will be loaded dynamically after invoice selection or manual adds -->
                    </tbody>
                    <tfoot>
                      <tr class="fw-bold">
                        <td colspan="3" class="text-end">Total Hold Qty</td>
                        <td><input type="text" id="totalHold" name="total_hold_qty" class="form-control text-center" value="0" readonly></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <a href="{{ route('stock-hold-list') }}" class="btn btn-light">Back to list</a>
                  </div>
                  <div>
                    <!-- Hidden fields -->
                    <input type="hidden" id="sale_id" name="sale_id" value="">
                    <input type="hidden" id="selected_booking_id" name="selected_booking_id" value="">
                    <button type="submit" class="btn btn-primary px-5">
                      <i class="bi bi-check-circle me-1"></i> Submit Stock Hold
                    </button>
                  </div>
                </div>

              </form>
            </div> <!-- card-body -->
          </div> <!-- card -->
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(function() {

  // ---------- helper ----------
  function recalcTotalHold() {
    let sum = 0;
    $('.hold-qty').each(function() {
      const v = parseFloat($(this).val() || 0);
      sum += isNaN(v) ? 0 : v;
    });
    $('#totalHold').val(sum);
  }

  // ---------- Load parties when type changes (unchanged) ----------
  $(document).on('change', '#vendor_type', function() {
    const type = $(this).val();
    const $party = $('#vendor_id');
    $party.prop('disabled', true).html('<option>Loading...</option>');

    $.get('{{ route("party.list") }}', { type: type })
      .done(function(rows) {
        $party.empty().append('<option disabled selected>Select Party</option>');
        rows.forEach(r => $party.append('<option value="' + r.id + '">' + r.text + '</option>'));
        $party.prop('disabled', false);
        $('#invoice_id').empty().append('<option selected disabled>Select Invoice</option>');
        $('#itemTableBody').empty();
        $('#totalHold').val('0');
        $('#customer_name,#tel,#remarks,#manual_stock').val('');
        $('#selected_booking_id').val('');
        $('#sale_id').val('');
      })
      .fail(function() {
        $party.empty().append('<option disabled>Error</option>');
      });
  });

  // ---------- Party change: load invoices & party details ----------
  $(document).on('change', '#vendor_id', function() {
    const $this = $(this);
    const partyId = $this.val();
    const type = $('#vendor_type').val();
    const optText = $this.find('option:selected').text() || '';

    $('#customer_name').val(optText);

    const $inv = $('#invoice_id');
    $inv.prop('disabled', true).html('<option>Loading...</option>');
    $('#itemTableBody').empty();
    $('#totalHold').val('0');
    $('#selected_booking_id').val('');
    $('#sale_id').val('');

    $.get('{{ url("party") }}/' + partyId + '/invoices', { type: type })
      .done(function(rows) {
        $inv.empty().append('<option disabled selected>Select Invoice</option>');
        rows.forEach(r => $inv.append('<option value="' + r.id + '">' + r.text + '</option>'));
        $inv.prop('disabled', false);
      })
      .fail(function() {
        $inv.empty().append('<option disabled>Error</option>').prop('disabled', false);
      });

    $.get('{{ route("customers.show", ["id" => "__ID__"]) }}'.replace('__ID__', partyId) + '?type=' + type)
      .done(function(d) {
        $('#customer_name').val(d.customer_name || d.name || optText || '');
        $('#tel').val(d.mobile || d.phone || '');
      })
      .fail(function(){});
  });

  // ---------- Invoice selected: load invoice items ----------
  $(document).on('change', '#invoice_id', function() {
    const bookingId = $(this).val();
    if (!bookingId) return;

    $('#sale_id').val(bookingId);
    $('#selected_booking_id').val(bookingId);

    $.get('{{ url("invoice") }}/' + bookingId + '/items')
      .done(function(items) {
        const $body = $('#itemTableBody').empty();
        let total = 0;

        if (!items || items.length === 0) {
          $body.append('<tr><td colspan="4">No items found for this invoice</td></tr>');
        } else {
          items.forEach(it => {
            const saleQty = parseFloat(it.sales_qty ?? it.quantity ?? 0);
            const holdQty = parseFloat(it.hold_qty ?? 0);

            const tr = `<tr data-item-id="${it.item_id}">
              <td class="text-center">${it.item_id}</td>
              <td class="text-start">${it.item_name ?? (it.product_name ?? 'Product #' + it.product_id)}</td>
              <td>
                <input type="number" min="0" class="form-control form-control-sm text-center sale-qty" value="${saleQty}" readonly />
              </td>
              <td>
                <input type="number" step="any" min="0"
                  name="items[${it.item_id}][hold_qty]"
                  class="form-control form-control-sm text-center hold-qty"
                  value="${holdQty}"
                  data-item-id="${it.item_id}" />
                <input type="hidden" name="items[${it.item_id}][sale_qty]" value="${saleQty}">
                <input type="hidden" name="items[${it.item_id}][item_id]" value="${it.item_id}">
                <input type="hidden" name="items[${it.item_id}][product_id]" value="${it.product_id ?? ''}">
                <input type="hidden" name="items[${it.item_id}][warehouse_id]" value="${it.warehouse_id ?? ''}">
              </td>
            </tr>`;

            $body.append(tr);
            total += parseFloat(holdQty || 0);
          });
        }

        $('#totalHold').val(total);
      })
      .fail(function() {
        $('#itemTableBody').empty().append('<tr><td colspan="4">Error loading items</td></tr>');
        $('#totalHold').val('0');
      });
  });

  // ---------- recalc when user edits hold qty ----------
  $(document).on('input', '.hold-qty', function() {
    recalcTotalHold();
  });

  // ---------- PRODUCT SEARCH (manual): debounce + suggestions ----------
  let selectedManualProduct = null;
  let productSearchTimer = null;

  $(document).on('input', '#product_search', function() {
    const q = $(this).val().trim();
    const $sug = $('#product_suggestions');

    if (productSearchTimer) clearTimeout(productSearchTimer);
    if (!q) { $sug.hide().empty(); selectedManualProduct = null; $('#manual_stock').val(''); return; }

    productSearchTimer = setTimeout(() => {
      $.get('{{ route("products.search") }}', { q: q })
        .done(function(rows) {
          $sug.empty();
          if (!rows || rows.length === 0) { $sug.hide(); selectedManualProduct = null; $('#manual_stock').val(''); return; }

          rows.forEach(p => {
            const text = (p.name || p.product_name || 'Product #'+p.id) ;
            const $item = $(`<button type="button" class="list-group-item list-group-item-action"></button>`);
            $item.html(`<div class="d-flex justify-content-between"><div>${text}</div><small class="text-muted">Stock: ${p.stock ?? '-'}</small></div>`);
            $item.data('prod', p);
            $sug.append($item);
          });
          $sug.show();
        })
        .fail(function() { $sug.hide().empty(); selectedManualProduct=null; $('#manual_stock').val(''); });
    }, 220);
  });

  // click suggestion
  $(document).on('click', '#product_suggestions .list-group-item', function() {
    const p = $(this).data('prod');
    selectedManualProduct = p;
    $('#product_search').val(p.name || p.product_name || ('Product #' + p.id));
    $('#product_suggestions').hide().empty();
    $('#manual_stock').val(p.stock ?? '');
  });

  // ---------- ADD manual product (holds only; sale_qty NOT used) ----------
  $(document).on('click', '#btn_add_manual', function() {
    if (!selectedManualProduct) {
      alert('Please select a product from suggestions first.');
      return;
    }

    const product = selectedManualProduct;
    const holdQty = parseFloat($('#manual_hold_qty').val() || 0);

    if (holdQty <= 0) {
      alert('Hold quantity must be greater than 0.');
      return;
    }

    // unique manual key
    const rowKey = 'manual_' + product.id + '_' + Date.now();

    const tr = `<tr data-manual="1" data-key="${rowKey}">
      <td class="text-center">${product.id}</td>
      <td class="text-start">${product.name ?? product.product_name ?? 'Product #' + product.id}</td>
      <td>
        <input type="text" class="form-control form-control-sm text-center sale-qty" value="—" readonly />
      </td>
      <td>
        <input type="number" step="any" min="0"
          name="items[${rowKey}][hold_qty]"
          class="form-control form-control-sm text-center hold-qty"
          value="${holdQty}"
        />
        <input type="hidden" name="items[${rowKey}][sale_qty]" value="0">
        <input type="hidden" name="items[${rowKey}][item_id]" value="">
        <input type="hidden" name="items[${rowKey}][product_id]" value="${product.id}">
        <input type="hidden" name="items[${rowKey}][warehouse_id]" value="">
        <div class="mt-1 text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-manual">Remove</button></div>
      </td>
    </tr>`;

    $('#itemTableBody').append(tr);
    recalcTotalHold();

    // clear manual form
    selectedManualProduct = null;
    $('#product_search').val('');
    $('#manual_stock').val('');
    $('#manual_hold_qty').val(1);
  });

  // remove manual row
  $(document).on('click', '.btn-remove-manual', function() {
    $(this).closest('tr').remove();
    recalcTotalHold();
  });

  // ---------- FORM SUBMIT (AJAX) ----------
  $('#stockHoldForm').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    $.ajax({
      url: $form.attr('action'),
      method: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          alert(res.message || 'Saved');
          // optional: redirect to list
          window.location.href = "{{ route('stock-hold-list') }}";
        } else {
          alert(res.message || 'Saved (no message)');
        }
      },
      error: function(xhr) {
        if (xhr.responseJSON) {
          const json = xhr.responseJSON;
          if (json.errors) {
            let msg = '';
            for (let k in json.errors) msg += json.errors[k].join(', ') + '\n';
            alert('Validation failed:\n' + msg);
          } else if (json.message) {
            alert('Error: ' + json.message + (json.detail ? '\n' + json.detail : ''));
          } else {
            alert('Unexpected error. Check console/network tab.');
            console.error(xhr.responseText);
          }
        } else {
          alert('Server error. See console.');
          console.error(xhr.responseText);
        }
      }
    });
  });

});
</script>
@endsection
