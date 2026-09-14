<script>
    $(document).ready(function() {
        function uncheckItem($item) {
            $item.find('input[type="checkbox"]').prop('checked', false);
            $item.removeClass('selected');
        }

        function getCheckedValues(listId, inputName) {
            const values = [];
            $('#' + listId + ' .filter-item:visible input[name="' + inputName + '"]:checked').each(function() {
                values.push(String($(this).val()));
            });
            return values;
        }

        $('.filter-item').on('click', function(e) {
            if ($(e.target).is('input')) return;
            const $cb = $(this).find('input[type="checkbox"]');
            $cb.prop('checked', !$cb.prop('checked'));
            $(this).toggleClass('selected', $cb.prop('checked'));

            const listId = $(this).closest('.filter-list').attr('id');
            if (listId === 'party-list') {
                autoSelectPartyType($(this));
            } else if (listId === 'account-list') {
                autoSelectMainHead($(this));
            }
        });

        function autoSelectPartyType($partyItem) {
            if (!$partyItem.find('input[type="checkbox"]').is(':checked')) return;
            const partyType = String($partyItem.data('party-type') || '');
            if (!partyType) return;
            $('#party-type-list .filter-item').each(function() {
                const $typeCb = $(this).find('input[name="party_type[]"]');
                if (String($typeCb.val()) === partyType) {
                    $typeCb.prop('checked', true);
                    $(this).addClass('selected');
                }
            });
        }

        function autoSelectMainHead($accountItem) {
            if (!$accountItem.find('input[type="checkbox"]').is(':checked')) return;
            const headId = String($accountItem.data('head-id') || '');
            if (!headId) return;
            $('#main-head-list .filter-item').each(function() {
                const $headCb = $(this).find('input[name="main_head[]"]');
                if (String($headCb.val()) === headId) {
                    $headCb.prop('checked', true);
                    $(this).addClass('selected');
                }
            });
        }

        $('.select-all').on('change', function() {
            const target = $(this).data('target');
            const checked = $(this).is(':checked');
            $('#' + target + ' .filter-item:visible').each(function() {
                $(this).find('input[type="checkbox"]').prop('checked', checked);
                $(this).toggleClass('selected', checked);
            });
        });

        $('#globalSelectAll').on('change', function() {
            const checked = $(this).is(':checked');
            $('.select-all').prop('checked', checked).trigger('change');
        });

        $('#partySearch').on('keyup', function() {
            filterByPartyType();
        });

        function filterByPartyType() {
            const selectedTypes = getCheckedValues('party-type-list', 'party_type[]');
            const searchTerm = ($('#partySearch').val() || '').toLowerCase();

            $('#party-list .filter-item').each(function() {
                const partyType = String($(this).data('party-type') || '');
                const matchesType = selectedTypes.length === 0 || selectedTypes.includes(partyType);
                const matchesSearch = !searchTerm || ($(this).data('search') || '').includes(searchTerm);
                const visible = matchesType && matchesSearch;
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#party-type-list .filter-item').on('click', function() {
            setTimeout(filterByPartyType, 50);
        });

        $('#accountSearch').on('keyup', function() {
            filterByMainHead();
        });

        function filterByMainHead() {
            const selectedMainHeads = getCheckedValues('main-head-list', 'main_head[]');
            const searchTerm = ($('#accountSearch').val() || '').toLowerCase();

            $('#account-list .filter-item').each(function() {
                const headId = String($(this).data('head-id') || '');
                const matchesHead = selectedMainHeads.length === 0 || selectedMainHeads.includes(headId);
                const matchesSearch = !searchTerm || ($(this).data('search') || '').includes(searchTerm);
                const visible = matchesHead && matchesSearch;
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#main-head-list .filter-item').on('click', function() {
            setTimeout(filterByMainHead, 50);
        });

        function filterByGroup() {
            const selectedGroups = getCheckedValues('group-list', 'user_group[]');
            if (selectedGroups.length === 0) {
                $('#officer-list .filter-item, #party-list .filter-item, #account-list .filter-item').show();
                return;
            }
            ['officer-list', 'party-list', 'account-list'].forEach(function(listId) {
                $('#' + listId + ' .filter-item').each(function() {
                    const groups = String($(this).data('groups') || '').split(',').filter(Boolean);
                    const visible = groups.length === 0 || groups.some(g => selectedGroups.includes(g));
                    $(this).toggle(visible);
                    if (!visible) uncheckItem($(this));
                });
            });
        }

        $('#group-list .filter-item').on('click', function() {
            setTimeout(filterByGroup, 50);
        });
    });
</script>
