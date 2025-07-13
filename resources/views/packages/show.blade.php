<x-admin>
    <div class="container py-5">
        <!-- Package Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ $package['name'] }} Package</h2>
            <span class="badge bg-primary fs-6 px-3 py-2">Premium Selection</span>
        </div>

        <!-- Package Details Card -->
        <div class="card shadow-lg rounded-4 mb-5 overflow-hidden">
            <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3 class="mb-1 fw-bold">ETB {{ number_format($package['price']) }}</h3>
                <small class="opacity-75">One-time Investment</small>
            </div>
            
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-gift text-success me-3 fs-4"></i>
                            <div>
                                <h6 class="mb-1">Product Value</h6>
                                <span class="text-muted">ETB {{ number_format($package['product_value']) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-percentage text-warning me-3 fs-4"></i>
                            <div>
                                <h6 class="mb-1">Commission (10%)</h6>
                                <span class="text-success fw-bold">ETB {{ number_format($package['product_value'] * 0.10) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-transparent text-center py-4">
                <form id="orderForm" method="POST" action="{{ route('admin.packages.order') }}">
                    @csrf
                    <input type="hidden" name="name" value="{{ $package['name'] }}">
                    <input type="hidden" name="price" value="{{ $package['price'] }}">
                    <input type="hidden" name="product_value" value="{{ $package['product_value'] }}">
                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 py-3 shadow-sm" id="orderBtn">
                        <i class="fas fa-bullhorn me-2"></i>
                        <span class="btn-text">Proceed to Advertise</span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Products Preview Section -->
        @if($randomImages->isNotEmpty())
            <div class="mb-4">
                <div class="d-flex align-items-center mb-4">
                    <i class="fas fa-eye text-primary me-2"></i>
                    <h4 class="mb-0">Included Products Preview</h4>
                    <span class="badge bg-secondary ms-2">{{ $randomImages->count() }} items</span>
                </div>
                
                <div class="row g-4">
                    @foreach($randomImages as $index => $image)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100 shadow-sm border-0 overflow-hidden position-relative">
                                <img src="{{ $image }}" 
                                     class="card-img-top" 
                                     alt="Product {{ $index + 1 }}"
                                     style="height: 200px; object-fit: cover; transition: transform 0.3s ease;">
                                <div class="card-img-overlay d-flex align-items-end p-0">
                                    <div class="bg-dark bg-opacity-75 text-white p-2 w-100">
                                        <small class="mb-0">Product {{ $index + 1 }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-box-open text-muted display-1 mb-3"></i>
                <h5 class="text-muted">No product previews available</h5>
                <p class="text-muted">Product images will be displayed here when available.</p>
            </div>
        @endif
    </div>

    <!-- Insufficient Balance Modal -->
    <div class="modal fade" id="insufficientBalanceModal" tabindex="-1" aria-labelledby="insufficientBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3 text-warning">Insufficient Balance</h4>
                    <p class="mb-4 text-muted">
                        <strong>Required Amount:</strong> ETB <span id="requiredAmount"></span><br>
                        <strong>Available Balance:</strong> ETB <span id="availableBalance"></span>
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="{{ route('admin.wallet.deposit') }}" class="btn btn-primary">Top Up Wallet</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="mb-3 text-success">Order Successful!</h4>
                    <p class="mb-4 text-muted">Your commission of <strong class="text-success">ETB <span id="commissionAmount"></span></strong> will be reflected in your dashboard within <strong>5 minutes</strong>.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="{{ route('admin.packages') }}" class="btn btn-primary">Proceed</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('orderBtn');
            const btnText = btn.querySelector('.btn-text');
            const spinner = btn.querySelector('.spinner-border');
            
            // Show loading state
            btn.disabled = true;
            btnText.textContent = 'Processing...';
            spinner.classList.remove('d-none');
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update modal content
                    document.getElementById('commissionAmount').textContent = data.commission;
                    
                    // Show success modal
                    const modal = new bootstrap.Modal(document.getElementById('successModal'));
                    modal.show();
                } else {
                    // Handle insufficient balance or other errors
                    if (data.required_amount && data.available_balance) {
                        // Show insufficient balance modal
                        document.getElementById('requiredAmount').textContent = data.required_amount;
                        document.getElementById('availableBalance').textContent = data.available_balance;
                        
                        const modal = new bootstrap.Modal(document.getElementById('insufficientBalanceModal'));
                        modal.show();
                    } else {
                        // Show generic error
                        alert('Error: ' + (data.message || 'Something went wrong'));
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            })
            .finally(() => {
                // Reset button state
                btn.disabled = false;
                btnText.textContent = 'Proceed to Advertise';
                spinner.classList.add('d-none');
            });
        });
    </script>

    <style>
        .card:hover img {
            transform: scale(1.05);
        }
        
        .bg-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .card-header.bg-gradient {
            border: none;
        }
        
        @media (max-width: 768px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
    </style>
</x-admin>