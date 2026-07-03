                <div class="card form-card mb-0">
                    <div class="card-footer bg-white">
                        <div class="d-flex flex-wrap justify-content-center w-100 bottom-bar-btns">
                            <button type="button" id="saveDraftBtn" class="btn btn-primary px-3 fw-bold shadow-sm" @if(($receipt->status ?? 'draft') == 'posted') disabled @endif>
                                <u>S</u>ave <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+S</kbd>
                            </button>
                            <button type="button" id="editInvoiceBtn" class="btn btn-warning px-3 fw-bold text-dark shadow-sm" disabled>
                                <u>E</u>dit <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+E</kbd>
                            </button>
                            <button type="button" id="postBtn" class="btn btn-success px-3 fw-bold shadow-sm" @if(($receipt->status ?? 'draft') == 'posted') disabled @endif>
                                <u>P</u>ost <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+&crarr;</kbd>
                            </button>
                            @if(($receipt->status ?? '') == 'posted' && !empty($showUnpost))
                            <button type="button" id="unpostBtn" class="btn btn-outline-danger px-3 fw-bold shadow-sm">
                                <u>U</u>npost
                            </button>
                            @endif
                            <button type="button" id="deleteBtn" class="btn btn-danger px-3 fw-bold shadow-sm" @if(empty($receipt->id) || ($receipt->status ?? '') == 'posted') disabled @endif onclick="handleCancel()">
                                <u>D</u>elete <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+D</kbd>
                            </button>
                            <a href="{{ !empty($receipt->id) ? route($printRoute, $receipt->id) : 'javascript:void(0)' }}" id="realPrintBtn" target="_blank" class="btn btn-info px-3 fw-bold text-dark shadow-sm {{ empty($receipt->id) ? 'pe-none opacity-50' : '' }}">
                                <u>P</u>rint <kbd style="font-size:10px;opacity:.8;margin-left:4px;color:#fff;">Ctrl+P</kbd>
                            </a>
                            <a href="{{ route($listRoute) }}" id="exitBtn" class="btn btn-secondary px-3 fw-bold shadow-sm text-white">
                                E<u>x</u>it <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Esc</kbd>
                            </a>
                            <a href="{{ route($newRoute) }}" id="newInvoiceBtn" class="btn btn-dark px-3 fw-bold shadow-sm text-white">
                                <u>N</u>ew <kbd style="font-size:10px;opacity:.8;margin-left:4px;">Ctrl+M</kbd>
                            </a>
                        </div>
                    </div>
                </div>
