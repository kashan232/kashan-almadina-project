<?php
$f = 'resources/views/admin_panel/vochers/income_vouchers/income_vouchers.blade.php';
$c = file_get_contents($f);

// 1. Remove @if(strtoupper($head->name) === 'INCOME') in select
$c = preg_replace('/@if\(strtoupper\(\$head->name\) === \'INCOME\'\)\s*/', '', $c);
$c = preg_replace('/\s*@endif\s*(?=\s*@endforeach\s*<\/select>)/', "\n", $c);

// 2. Add allAccounts inside rowPartySelect select element
$targetSelect = '<select name="party_id[]" class="form-select form-select-sm rowPartySelect select2" data-selected="{{ $pIds[$idx] ?? \'\' }}">' . "\n" .
                '                                                 <option value="">Select Party...</option>' . "\n" .
                '                                             </select>';

$newSelectContent = '<select name="party_id[]" class="form-select form-select-sm rowPartySelect select2" data-selected="{{ $pIds[$idx] ?? \'\' }}">' . "\n" .
                    '                                                 <option value="">Select Party...</option>' . "\n" .
                    '                                                 @if(isset($allAccounts))' . "\n" .
                    '                                                     @foreach($allAccounts as $acc)' . "\n" .
                    '                                                     <option value="{{ $acc->id }}" data-head-id="{{ $acc->head_id }}" data-code="{{ $acc->account_code }}" {{ ($pIds[$idx] ?? \'\') == $acc->id ? \'selected\' : \'\' }}>{{ $acc->title }}</option>' . "\n" .
                    '                                                     @endforeach' . "\n" .
                    '                                                 @endif' . "\n" .
                    '                                             </select>';

$c = str_replace($targetSelect, $newSelectContent, $c);

// 3. Update rowPartySelect change listener to auto-set Account Head and Code
$oldPartyJs = "    // 👤 Row Party Logic\n" .
              "    $(document).on('change', '.rowPartyType', function() {\n" .
              "        let type = $(this).val();\n" .
              "        let \$row = $(this).closest('tr');\n" .
              "        let \$select = \$row.find('.rowPartySelect');\n" .
              "        let selected = \$select.data('selected');\n" .
              "        \$row.find('.rowPartyCode').val('');\n" .
              "        \$select.html('<option value=\"\">Loading...</option>');\n" .
              "        if(type) {\n" .
              "            let url = (['vendor','customer','walkin'].includes(type)) ? '{{ route(\"party.list\") }}?type=' + type : '{{ url(\"get-accounts-by-head\") }}/' + type;\n" .
              "            $.get(url, function(res) {\n" .
              "                \$select.html('<option value=\"\">Select Party...</option>');\n" .
              "                res.forEach(i => {\n" .
              "                    let code = i.account_code || '';\n" .
              "                    \$select.append(`<option value=\"\${i.id}\" data-code=\"\${code}\" \${i.id == selected ? 'selected' : ''}>\${i.text || i.title}</option>`);\n" .
              "                });\n" .
              "                if(selected) {\n" .
              "                    let code = \$select.find('option:selected').attr('data-code');\n" .
              "                    \$row.find('.rowPartyCode').val(code || selected);\n" .
              "                }\n" .
              "            });\n" .
              "        }\n" .
              "    });\n\n" .
              "    $('.rowPartyType').each(function() { if ($(this).val()) $(this).trigger('change'); });\n\n" .
              "    $(document).on('change', '.rowPartySelect', function() {\n" .
              "        let code = $(this).find('option:selected').attr('data-code');\n" .
              "        $(this).closest('tr').find('.rowPartyCode').val(code || $(this).val() || '');\n" .
              "    });";

$newPartyJs = "    // 👤 Row Party Logic\n" .
              "    $(document).on('change', '.rowPartySelect', function() {\n" .
              "        let \$row = \$(this).closest('tr');\n" .
              "        let \$opt = \$(this).find('option:selected');\n" .
              "        let code = \$opt.attr('data-code') || \$opt.data('code');\n" .
              "        let headId = \$opt.attr('data-head-id') || \$opt.data('head-id');\n" .
              "        \$row.find('.rowPartyCode').val(code || '');\n" .
              "        if (headId) {\n" .
              "            let \$headSelect = \$row.find('.rowPartyType');\n" .
              "            \$headSelect.val(headId).trigger('change');\n" .
              "        }\n" .
              "    });\n\n" .
              "    $(document).on('change', '.rowPartyType', function(e, isUserAction) {\n" .
              "        let \$row = \$(this).closest('tr');\n" .
              "        let headId = \$(this).val();\n" .
              "        let \$subSelect = \$row.find('.rowPartySelect');\n" .
              "        let selectedSubId = \$subSelect.val();\n" .
              "        if (headId && isUserAction) {\n" .
              "            $.get('{{ url(\"get-accounts-by-head\") }}/' + headId, function(res) {\n" .
              "                \$subSelect.html('<option value=\"\">Select Party...</option>');\n" .
              "                res.forEach(acc => {\n" .
              "                    let sel = (acc.id == selectedSubId) ? 'selected' : '';\n" .
              "                    \$subSelect.append(`<option value=\"\${acc.id}\" data-head-id=\"\${headId}\" data-code=\"\${acc.account_code}\" \${sel}>\${acc.title}</option>`);\n" .
              "                });\n" .
              "                \$subSelect.trigger('change.select2');\n" .
              "            });\n" .
              "        }\n" .
              "    });";

$c = str_replace($oldPartyJs, $newPartyJs, $c);

// 4. Update btnAddRow template string
$oldBtnAdd = "'<td><select name=\"party_type[]\" class=\"form-select form-select-sm rowPartyType select2\"><option value=\"\">Select Type...</option>@foreach(\$AccountHeads as \$head) @if(strtoupper(\$head->name) === 'INCOME')<option value=\"{{ \$head->id }}\">{{ addslashes(\$head->name) }}</option>@endif @endforeach</select></td>' +\n" .
             "            '<td><input type=\"text\" name=\"row_party_code[]\" class=\"form-control form-control-sm text-center fw-bold text-danger rowPartyCode\" placeholder=\"Code\"></td>' +\n" .
             "            '<td><select name=\"party_id[]\" class="form-select form-select-sm rowPartySelect select2\"><option value=\"\">Select Party...</option></select></td>' +";

$newBtnAdd = "'<td><select name=\"party_type[]\" class=\"form-select form-select-sm rowPartyType select2\"><option value=\"\">Select Type...</option>@foreach(\$AccountHeads as \$head)<option value=\"{{ \$head->id }}\">{{ addslashes(\$head->name) }}</option>@endforeach</select></td>' +\n" .
             "            '<td><input type=\"text\" name=\"row_party_code[]\" class=\"form-control form-control-sm text-center fw-bold text-danger rowPartyCode\" placeholder=\"Code\"></td>' +\n" .
             "            '<td><select name=\"party_id[]\" class=\"form-select form-select-sm rowPartySelect select2\"><option value=\"\">Select Party...</option>@if(isset(\$allAccounts))@foreach(\$allAccounts as \$acc)<option value=\"{{ \$acc->id }}\" data-head-id=\"{{ \$acc->head_id }}\" data-code=\"{{ \$acc->account_code }}\">{{ addslashes(\$acc->title) }}</option>@endforeach @endif</select></td>' +";

$c = str_replace($oldBtnAdd, $newBtnAdd, $c);

file_put_contents($f, $c);
echo "INCOME FIX APPLIED";
