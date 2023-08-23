@extends('base')

@section('title', 'Transaction')

@section('header_title', 'Transaction')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-body table-responsive p-0">
          <table id="transactions" class="table table-hover text-nowrap">
            <caption>Transactions Data User</caption>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Amount</th>
                <th>Transaction Type</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @php $i = 1; @endphp
              @foreach($transactions as $transaction)
              <tr>
                <td>{{$i}}</td>
                <td>{{$transaction->user->name}}</td>
                <td>Rp {{number_format($transaction->amount)}}</td>
                <td>{{$transaction->transactionType->name}}</td>
                <td>{{$transaction->paymentMethod->name}}</td>
                <td>{{$transaction->status}}</td>
                <td>{{$transaction->updated_at}}</td>
              </tr>
              @php $i ++; @endphp
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('js')
<script>
  $('#transactions').DataTable();
</script>
@endsection
