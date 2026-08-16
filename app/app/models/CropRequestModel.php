<?php
class CropRequestModel
{
    use Model;

    protected $table = 'crop_requests';
    protected $allowedColumns = [
        'buyer_id',
        'crop_name',
        'quantity',
        'target_price',
        'delivery_date',
        'location',
        'status',
        'created_at'
    ];

    public function validate($data)
    {
        $this->errors = [];

        if (empty($data['crop_name']))
            $this->errors['crop_name'] = "Crop name is required";

        if (empty($data['quantity']) || $data['quantity'] <= 0)
            $this->errors['quantity'] = "Quantity must be a positive number";

        if (empty($data['target_price']) || $data['target_price'] <= 0)
            $this->errors['target_price'] = "Target price must be a positive number";

        if (empty($data['delivery_date']))
            $this->errors['delivery_date'] = "Delivery date is required";
        else {
            $deliveryDate = strtotime($data['delivery_date']);
            $today = strtotime(date('Y-m-d'));
            if ($deliveryDate <= $today)
                $this->errors['delivery_date'] = "Delivery date must be in the future";
        }

        if (empty($data['location']))
            $this->errors['location'] = "Location is required";

        if (empty($this->errors))
            return true;
        return false;
    }

    public function getRequestsByBuyer($buyer_id)
    {
        return $this->where(['buyer_id' => $buyer_id]);
    }

    public function getRequestById($id)
    {
        return $this->first(['id' => $id]);
    }

    public function getAllRequests()
    {
        return $this->findAll();
    }

    public function getRequestsByStatus($status)
    {
        return $this->where(['status' => $status]);
    }
}
