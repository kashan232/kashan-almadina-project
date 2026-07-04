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
        });

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
            const term = $(this).val().toLowerCase();
            $('#party-list .filter-item').each(function() {
                const match = ($(this).data('search') || '').includes(term);
                $(this).toggle(match);
                if (!match) uncheckItem($(this));
            });
        });

        $('#accountSearch').on('keyup', function() {
            filterByMainHead();
        });

        function filterByMainHead() {
            const selectedMainHeads = getCheckedValues('main-head-list', 'main_head[]');
            const selectedSubHeads = getCheckedValues('sub-head-list', 'sub_head[]');

            $('#sub-head-list .filter-item').each(function() {
                const headId = String($(this).data('head-id') || '');
                const visible = selectedMainHeads.length === 0 || selectedMainHeads.includes(headId);
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });

            const activeHeads = selectedSubHeads.length > 0 ? selectedSubHeads : selectedMainHeads;

            $('#account-list .filter-item').each(function() {
                const headId = String($(this).data('head-id') || '');
                const searchTerm = ($('#accountSearch').val() || '').toLowerCase();
                const matchesHead = activeHeads.length === 0 || activeHeads.includes(headId);
                const matchesSearch = !searchTerm || ($(this).data('search') || '').includes(searchTerm);
                const visible = matchesHead && matchesSearch;
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#main-head-list .filter-item, #sub-head-list .filter-item').on('click', function() {
            setTimeout(filterByMainHead, 50);
        });

        function filterByGroup() {
            const selectedGroups = getCheckedValues('group-list', 'user_group[]');
            if (selectedGroups.length === 0) {
                $('#officer-list .filter-item').show();
                return;
            }
            $('#officer-list .filter-item').each(function() {
                const groups = String($(this).data('groups') || '').split(',').filter(Boolean);
                const visible = groups.length === 0 || groups.some(g => selectedGroups.includes(g));
                $(this).toggle(visible);
                if (!visible) uncheckItem($(this));
            });
        }

        $('#group-list .filter-item').on('click', function() {
            setTimeout(filterByGroup, 50);
        });
    });
</script>
