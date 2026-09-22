@extends('pdf')
@section('content')
    <div class="invoice">
        <div class="invoice-company text-inverse fw-600">
            {{ $appName }}
        </div>
        <div class="invoice-header">
            <div class="invoice-to">
                <address class="m-t-5 m-b-5">
                    <strong class="text-inverse">{{ $customer->name }}</strong><br/>
                    {{$customer->address}}<br/>
                    Phone: {{$customer->phone}}<br/>
                    email: {{$customer->email}}
                </address>
            </div>
            <div class="invoice-date">
                <div class="date text-inverse m-t-5">
                    {{ $order->created_at->format('M d, Y') }}
                </div>
                <div class="invoice-detail">
                    {{$order->invoice_no}}<br/>
                    {{$order->payment_mode}}
                </div>
                <small>User: {{$user->name}}</small>

            </div>
        </div>
        <div class="invoice-content">
            <div class="table-responsive">
                <table class="table table-invoice">
                    <thead>
                    <tr>
                        <th>PRODUCT</th>
                        <th class="text-center" width="100px">QTY</th>
                        <th class="text-center" width="80px">RATE</th>
                        <th class="text-right" width="100px">TOTAL</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                            <span class="text-inverse">
                                {{$item->sku}}
                            </span><br/>
                                <small>{{$item->product->name}}</small>
                            </td>
                            <td class="text-center">{{$item->qty}} {{$item->unit}}</td>
                            <td class="text-center">
                                {{ number_format($item->price, 0, ".", ",")}}
                            </td>
                            <td class="text-right">
                                {{ number_format($item->qty * $item->price, 0, ".", ",")}}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="invoice-price">
                <div class="invoice-price-left">
                    <div class="invoice-price-row">
                        <div class="sub-price">
                            <small>SUBTOTAL</small>
                            <span class="text-inverse">
                                        {{ number_format($order->total, 0, ".", ",")}}
                                    </span>
                        </div>
                        <div class="sub-price">
                            <i class="fa fa-plus text-muted"></i>
                        </div>
                        @if($order->discount)

                            <div class="sub-price">
                                <small>Discount</small>
                                <span class="text-inverse">{{$order->discount}}</span>
                            </div>
                        @endif

                    </div>
                </div>
                <div class="invoice-price-right">
                    <small>TOTAL</small>
                    <span class="fw-600">
                        {{ number_format($order->net_total, 0, ".", ",")}}
                    </span>
                </div>
            </div>
        </div>
        <div class="invoice-note">
            * Make all cheques payable to [Your Company Name]<br/>
            * Payment is due within 30 days<br/>
            * If you have any questions concerning this invoice, contact [Name, Phone Number, Email]
        </div>
        <div class="invoice-footer">
            <p class="text-center m-b-5 fw-600">
                THANK YOU FOR YOUR BUSINESS
            </p>
            <p class="text-center">
                <span class="m-r-10"><i class="fa fa-fw fa-lg fa-globe"></i> matiasgallipoli.com</span>
                <span class="m-r-10"><i class="fa fa-fw fa-lg fa-phone-volume"></i> T:016-18192302</span>
                <span class="m-r-10"><i class="fa fa-fw fa-lg fa-envelope"></i> rtiemps@gmail.com</span>
            </p>
        </div>
    </div>
@endsection
