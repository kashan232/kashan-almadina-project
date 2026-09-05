                <div class="card form-card mb-0">
                    <div class="card-footer bg-white">
                        <div class="d-flex flex-wrap justify-content-center align-items-center w-100 bottom-bar-btns">
                            <button type="button" id="saveDraftBtn" class="btn btn-primary shadow-sm" @if(($receipt->status ?? 'draft') == 'posted') disabled @endif>
                                <i class="fa fa-floppy-o me-1"></i> Save <kbd>Ctrl+S</kbd>
                            </button>
                            <button type="button" id="editInvoiceBtn" class="btn btn-warning text-dark shadow-sm" disabled>
                                <i class="fa fa-pencil me-1"></i> Edit <kbd style="color:#000;">Ctrl+E</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-success shadow-sm" @if(($receipt->status ?? 'draft') == 'posted') disabled @endif>
                                <i class="fa fa-check-circle me-1"></i> Post <kbd>Ctrl+&crarr;</kbd>
                            </button>
                            @if(($receipt->status ?? '') == 'posted' && !empty($showUnpost))
                            <button type="button" id="unpostBtn" class="btn btn-outline-danger shadow-sm">
                                <i class="fa fa-undo me-1"></i> Unpost
                            </button>
                            @endif
                            <button type="button" id="deleteBtn" class="btn btn-danger shadow-sm" @if(empty($receipt->id) || ($receipt->status ?? '') == 'posted') disabled @endif onclick="handleCancel()">
                                <i class="fa fa-trash me-1"></i> Delete <kbd>Ctrl+D</kbd>
                            </button>
                            <a href="{{ !empty($receipt->id) ? route($printRoute, $receipt->id) : 'javascript:void(0)' }}" id="realPrintBtn" target="_blank" class="btn btn-info text-dark shadow-sm {{ empty($receipt->id) ? 'pe-none opacity-50' : '' }}">
                                <i class="fa fa-print me-1"></i> Print <kbd style="color:#000;">Ctrl+P</kbd>
                            </a>
                            <a href="{{ route($listRoute) }}" id="exitBtn" class="btn btn-secondary shadow-sm text-white">
                                <i class="fa fa-times-circle me-1"></i> Exit <kbd>Esc</kbd>
                            </a>
                            <a href="{{ route($newRoute) }}" id="newInvoiceBtn" class="btn btn-dark shadow-sm text-white">
                                <i class="fa fa-plus-circle me-1"></i> New <kbd>Ctrl+M</kbd>
                            </a>
                        </div>
                    </div>
                </div>
