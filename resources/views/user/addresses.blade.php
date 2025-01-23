@extends('layouts.app')
@section('content')
    <main class="pt-90" style="padding-top: 0px;">
        <div class="mb-4 pb-4"></div>
        <section class="my-account container">
            <h2 class="page-title">Addresses</h2>
            <div class="row">
                <div class="col-lg-2">
                    @include('user.account-nav')
                </div>

                <div class="col-lg-10">
                    <div class="d-flex justify-content-end mb-3">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">Add New Address</button>
                    </div>
                    
                    @if(Session::has('status'))
                        <div class="alert alert-success">{{ Session::get('status') }}</div>
                    @endif

                    @if($addresses->count() > 0)
                        <div class="row">
                            @foreach($addresses as $address)
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $address->name }}</h5>
                                        <p class="card-text">
                                            {{ $address->address }}<br>
                                            {{ $address->locality }}<br>
                                            {{ $address->city }}, {{ $address->zip }}<br>
                                            {{ $address->country }}<br>
                                            Phone: {{ $address->phone }}
                                        </p>
                                        @if($address->isdefault)
                                            <span class="badge bg-success">Default Address</span>
                                        @endif
                                        <div class="mt-3">
                                            <button class="btn btn-sm btn-primary edit-address" 
                                                data-address="{{ json_encode($address) }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editAddressModal">
                                                Edit
                                            </button>
                                            <form action="{{ route('user.address.delete', $address->id) }}" 
                                                method="POST" 
                                                class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this address?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p>No addresses found. Add your first address.</p>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('user.address.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Locality (Optional)</label>
                            <input type="text" class="form-control" name="locality">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country" required>
                            </div>
                            <div class="col">
                                <label class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" name="zip" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Address Modal -->
    <div class="modal fade" id="editAddressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editAddressForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <!-- Same fields as Add Address Modal -->
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Locality</label>
                            <input type="text" class="form-control" name="locality" id="edit_locality" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_address" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="city" id="edit_city" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Country</label>
                                <input type="text" class="form-control" name="country" id="edit_country" required>
                            </div>
                            <div class="col">
                                <label class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" name="zip" id="edit_zip" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Address</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle edit button click
        document.querySelectorAll('.edit-address').forEach(button => {
            button.addEventListener('click', function() {
                const address = JSON.parse(this.dataset.address);
                document.getElementById('editAddressForm').action = `/account-addresses/update/${address.id}`;
                
                // Fill the form fields
                document.getElementById('edit_name').value = address.name;
                document.getElementById('edit_phone').value = address.phone;
                document.getElementById('edit_locality').value = address.locality;
                document.getElementById('edit_address').value = address.address;
                document.getElementById('edit_city').value = address.city;
                document.getElementById('edit_country').value = address.country;
                document.getElementById('edit_zip').value = address.zip;
            });
        });
    });
</script>
@endpush
@endsection 