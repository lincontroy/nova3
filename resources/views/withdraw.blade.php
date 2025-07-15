<x-admin>
<div class="container py-5">

<h3>Withdraw</h3>
<form action="{{url('admin/wallet/withdraw')}}" method="POST" class="p-4 border rounded shadow-sm bg-light">
    @csrf
    <h4 class="mb-4">Payment Details</h4>

    <div class="mb-3">
        <label for="amount" class="form-label">Amount</label>
        <input
            type="number"
            class="form-control"
            id="amount"
            name="amount"
            placeholder="Enter amount"
            required
            min="0"
        >
    </div>
    <div class="mb-3">
        <label for="bank_details" class="form-label">Bank Details</label>
        <textarea
            class="form-control"
            id="bank_details"
            name="bank_details"
            rows="4"
            placeholder="Enter your bank name and account number"
            required
        ></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

</div>
</x-admin>