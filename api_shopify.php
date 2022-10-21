<?php
App::uses('AppController', 'Controller');

class Order_Controller extends AppController
{
  $store = 'SiteName';
  
  public function retrieveCustomerMetafield()
  {
      $customer_id = $this->request->data["customer_id"];
      $isEmail = false;
      if ($this->request->data["type"] == "email")
      {	
        $isEmail = true;
      }
      $url = "https://".$this->apidetails.'@'.$store.".myshopify.com/admin/customers/".$customer_id."/metafields.json";

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_VERBOSE, 0);
      curl_setopt($curl, CURLOPT_HEADER, 0);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      $response = curl_exec ($curl);
      $fields = json_decode($response);
      $addresses = '';

      foreach ($fields->{'metafields'} as $field)
      {
          $key = $field->{'key'};
          $namespace = $field->{'namespace'};
          $value = $field->{'value'};
          $value_type = $field->{'value_type'};
      if ($isEmail && $key == 'EmailAddress' && $namespace == 'c_f')
      {
        $addresses = $value;
      }
      elseif (!$isEmail && $key == 'PhysicalAddress' && $namespace == 'c_f')
      {
        $addresses = $value;
      }
    }

      echo json_encode(array('success' => true,'addresses' => $addresses, 'details' => $response));
      exit;
  }
  
  public function insertCustomerMetafield()
  {
      $addresses_in = $this->request->data["addresses"];
      $customer_id = $this->request->data["customer_id"];
      $key = "PhysicalAddress";
      if ($this->request->data["type"] == "email")
      {
              $key = "EmailAddress";
      }
      $addresses_in = json_encode($addresses_in);  
      $metafield = array(
          "metafield"=>array(
              "namespace"=> "c_f",
              "key"=> $key,
              "value"=>$addresses_in,
              "value_type"=>"string")
          );
      $url = "https://".$this->apidetails.'@'.$store.".myshopify.com/admin/customers/".$customer_id."/metafields.json";

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_VERBOSE, 0);
      curl_setopt($curl, CURLOPT_HEADER, 1);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($metafield));
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      $response = curl_exec ($curl);

      echo json_encode(array('success' => true, 'details' => $response));
      exit;
  }
  
  function insertTags($order_id,$tags)
  {
    $cnt = 0;

    $order = array(
        "draft_order"=>array(
            "id"=> $order_id,
            "tags"=> $tags)
        );
        $url = "https://".$this->apidetails.'@'.$store.".myshopify.com/admin/" .
          "draft_orders/".$order_id.".json";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_VERBOSE, 0);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($order));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec ($curl);
    curl_close ($curl);
  }  
  
  public function rectifyOrder()
  {
      $order_id = $this->request->data["order_id"];
      $firstname = $this->request->data["firstname"];
      $last = $this->request->data["last"];
      $company = $this->request->data["company"];
      $address1 = $this->request->data["address1"];
      $address2 = $this->request->data["address2"];
      $city = $this->request->data["city"];
      $state = $this->request->data["state"];
      $zip = $this->request->data["zip"];
      $email = $this->request->data["email"];

      $order = array(
      "draft_order"=>array(
          "customer"=>array(
        "first_name"=> $firstname,
        "last_name"=> $last,
        "email"=> $email
      ),
      "shipping_address"=>array(
        "first_name"=> $firstname,
        "last_name"=> $last,
        "address1"=> $address1,
        "phone"=> "",
        "city"=> $city,
        "province"=> $state,
        "country"=> "US",
        "zip"=> $zip
      ),
      "email"=> $email,
      "financial_status"=> "paid"
      ));

      $url = "https://".$this->apidetails.'@'.$store.".myshopify.com/admin/" .
        "draft_orders/".$order_id.".json";

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_VERBOSE, 0);
      curl_setopt($curl, CURLOPT_HEADER, 0);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      $response = curl_exec($curl);
      curl_close($curl);

      $orders = json_decode($response, true);
      $this->sendEmail(Tomsource_Redeemed,$order_id,$orders,$order['draft_order']['shipping_address']);

      $url = "https://".$this->apidetails.'@'.$store.".myshopify.com/admin/" .
              "draft_orders/".$order_id.".json";

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_VERBOSE, 0);
      curl_setopt($curl, CURLOPT_HEADER, 0);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($order));
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      $response = curl_exec($curl);
      curl_close($curl);

      $url = "https://".$this->apidetails.'@'.$store.".myshopify.com/admin/" .
              "draft_orders/".$order_id."/complete.json";

      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_VERBOSE, 0);
      curl_setopt($curl, CURLOPT_HEADER, 0);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($order));
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      $response = curl_exec($curl);
      curl_close($curl);

      echo json_encode(array('success' => true));
      exit;
  }  

    public function zones()
    {
      	$url = "https://".$this->apidetails.'@'.$store.".myshopify.com/admin/" .
              "shipping_zones.json";

              $curl = curl_init();
              curl_setopt($curl, CURLOPT_URL, $url);
              curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
              curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
              curl_setopt($curl, CURLOPT_VERBOSE, 0);
              curl_setopt($curl, CURLOPT_HEADER, 0);
              curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
              curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
              $response = curl_exec ($curl);
              curl_close ($curl);

              $earl = json_decode($response);
    	foreach ($earl->shipping_zones as $zone)
    	{
                    foreach ($zone->carrier_shipping_rate_providers as $localrate)
                    {
                    	$rate = $localrate->flat_modifier;
                    }
    		foreach ($zone->countries as $country)
    		{
    			if ($country->name != 'United States')
    			{	continue;	}
    			foreach ($country->provinces as $state)
    			{
    				array_push($this->states,array($state->code,$rate));
    			}
    		}
    	}
    	usort($this->states, array($this, "cmp"));
    	echo json_encode(array('states' => $this->states));
    	  exit;
    }

    function cmp($a, $b)
    {
        return strcmp($a[0], $b[0]);
    }
}
