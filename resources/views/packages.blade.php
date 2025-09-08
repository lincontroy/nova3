@php
    $userLevel = auth()->user()->level;

    $packages = collect([
        [
            'name' => 'Level 1',
            'price' => 250,
            'product_value' => 80000,
            'description' => 'Promote products worth GH¢ 80,000 and earn GH¢ 8,000.',
        ],
        [
            'name' => 'Level 2',
            'price' => 550,
            'product_value' => 120000,
            'description' => 'Drive GH¢ 120,000 in sales and pocket GH¢ 12,000.',
        ],
        [
            'name' => 'Level 3',
            'price' => 850,
            'product_value' => 160000,
            'description' => 'Advertise GH¢ 160,000 in products for a GH¢ 16,000 commission.',
        ],
        [
            'name' => 'Level 4',
            'price' => 1200,
            'product_value' => 200000,
            'description' => 'Take on GH¢ 200,000 and score GH¢ 20,000.',
        ],
        [
            'name' => 'Level 5',
            'price' => 2400,
            'product_value' => 240000,
            'description' => 'Reach the top with GH¢ 240,000 and cash in GH¢ 24,000!',
        ],
    ])->map(function ($pkg) {
        $pkg['commission'] = number_format($pkg['product_value'] * 0.10); // 10% commission
        return $pkg;
    });
@endphp

<x-admin>
<div class="container py-5">
    <div class="col-md-12 col-12">
        <div class="small-box bg-success" style="min-height: 200px; padding: 2rem;">
            <div class="inner">
                <h3>GH¢ {{ number_format(Auth::user()->total_commissions) }}</h3>
                <p>My Total Commissions</p>
            </div>
            <div class="icon">
                <i class="fas fa-list-alt"></i>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="small-box-footer">
                View <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <h2 class="text-center mb-5">Get Started</h2>
    <div class="row g-4">
        @foreach($packages as $index => $package)
            @php
                $packageLevel = $index + 1;
                $isAvailable = false;
                $isUnlocked = false;
                $isLocked = false;

                if ($userLevel == 0) {
                    $isAvailable = ($packageLevel == 1);
                    $isLocked = ($packageLevel > 1);
                } else {
                    if ($packageLevel <= $userLevel) {
                        $isUnlocked = true;
                    } elseif ($packageLevel == $userLevel + 1) {
                        $isAvailable = true;
                    } else {
                        $isLocked = true;
                    }
                }
            @endphp

            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 border-0 shadow rounded-4">
                    <div class="card-header text-white text-center py-3"
                        style="background: linear-gradient(to right, #007bff, #0056b3); border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                        <h5 class="mb-0">{{ $package['name'] }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <p class="mb-3 text-muted">
                            {{ $package['description'] }}
                        </p>
                        <p class="text-muted mb-2">
                            Product Value: <strong>GH¢{{ number_format($package['product_value']) }}</strong>
                        </p>
                        <p>
                            Commission: <strong>GH¢{{ $package['commission'] }} (10%)</strong>
                        </p>

                        @if($isUnlocked)
                            <span class="badge bg-success">Unlocked</span>
                        @elseif($isLocked)
                            <span class="badge bg-danger">Locked</span>
                        @endif
                    </div>
                    <div class="card-footer bg-transparent text-center">
                        @if($isAvailable)
                            <a href="{{ route('admin.packages.show', $package['name']) }}" class="btn btn-primary w-75 rounded-pill">
                                Select
                            </a>
                        @elseif($isUnlocked)
                            <button class="btn btn-success w-75 rounded-pill" disabled>Unlocked</button>
                        @else
                            <button class="btn btn-secondary w-75 rounded-pill" disabled>Locked</button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
</x-admin>
