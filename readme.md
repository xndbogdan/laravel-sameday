# xndbogdan/laravel-sameday

This package was created due to a need for a simple integration with laravel.
It was developed for Laravel 8 but could possibly be used with lower versions, as it doesn't have too many dependencies.

## Implementation example

    app()->sameday->login(env('SAMEDAY_AUTH_USER'), env('SAMEDAY_AUTH_PASSWORD'), env('SAMEDAY_HOST_URL'));
    $defaultPickupData =  app()->sameday->getDefaultPickupPoint();
    $defaultService =  app()->sameday->getDefaultService();
    
    $serviceId = $defaultService->id;
    
    $parcels = [];
    $totalWeight =  0;
    $numberOfPackages =  0;
    
    foreach($order->order_items as $product) {
	    
	    for($i=1; $i <= $product->quantity; $i++) {
	        array_push($parcels, [
	            'weight' => $product->weight,
	            'width' => $product->width,
	            'height' => $product->height,
	            'length' => $product->depth,
	        ]);
	        $totalWeight += $product->weight;
	    }
	    $numberOfPackages += $product->quantity;
	}
	
	$awb = app()->sameday->sendAwb(
        $defaultPickupData->id,
        Sameday::PACKAGE_TYPE_PARCEL,
        $numberOfPackages,
        $serviceId,
        $parcels,
        $totalWeight,
        0,
        $paidOnline ? 0 : $order->subtotal,
        Sameday::AWB_PAYMENT_CLIENT,
        [
            'county' => app()->sameday->getCountyId($address->region),
            'city' => app()->sameday->getCityId($address->city),
            'address' => $address->address,
            'name' => $address->first_name . ' ' . $address->last_name,
            'phoneNumber' => $address->phone,
            'personType' => $address->cif ? 1 : 0,
        ],
        (isset($defaultPickupData->pickupPointContactPerson[0]) ? $defaultPickupData->pickupPointContactPerson[0]->id : null)
    );
    
    $order->update([
        'awb_number' => $awb->awbNumber,
        'awb_pdf' => $awb->pdfLink,
    ]);


## Getting pdf based on the order's awb

This was routed and implemented in a controller to be called from Nova and the user's "orders" section, on orders with an AWB.

    public function samedayPdf($awb, Request $request) {
   		app()->sameday->login(env('SAMEDAY_AUTH_USER'), env('SAMEDAY_AUTH_PASSWORD'), env('SAMEDAY_HOST_URL'));
   		return  app()->sameday->getPdf($awb);
    }