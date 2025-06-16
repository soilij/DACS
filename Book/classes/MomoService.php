<?php
/**
 * MomoService Class
 * Xử lý tích hợp thanh toán với MoMo QR Code
 */
class MomoService {
    private $config;
    
    /**
     * Khởi tạo service với cấu hình
     * 
     * @param array $config Mảng cấu hình kết nối MoMo
     */
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Tạo URL thanh toán MoMo
     * 
     * @param string $orderId Mã đơn hàng
     * @param int $amount Số tiền thanh toán
     * @param string $orderInfo Thông tin đơn hàng
     * @return array Kết quả từ MoMo API
     */
    public function createPaymentUrl($orderId, $amount, $orderInfo) {
        $endpoint = $this->config['momoApiUrl'];
        
        // Chuẩn bị dữ liệu gửi đến MoMo
        $rawData = [
            'partnerCode' => $this->config['partnerCode'],
            'accessKey' => $this->config['accessKey'],
            'requestId' => time() . '',
            'amount' => (string)$amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'returnUrl' => $this->config['returnUrl'],
            'notifyUrl' => $this->config['notifyUrl'],
            'requestType' => $this->config['requestType'],
        ];
        
        // Tạo signature
        $rawHash = "accessKey=" . $rawData['accessKey'] .
                  "&amount=" . $rawData['amount'] .
                  "&extraData=" .
                  "&ipnUrl=" . $rawData['notifyUrl'] .
                  "&orderId=" . $rawData['orderId'] .
                  "&orderInfo=" . $rawData['orderInfo'] .
                  "&partnerCode=" . $rawData['partnerCode'] .
                  "&redirectUrl=" . $rawData['returnUrl'] .
                  "&requestId=" . $rawData['requestId'] .
                  "&requestType=" . $rawData['requestType'];
        
        $signature = hash_hmac('sha256', $rawHash, $this->config['secretKey']);
        
        $data = array_merge($rawData, [
            'extraData' => '',
            'ipnUrl' => $rawData['notifyUrl'],
            'redirectUrl' => $rawData['returnUrl'],
            'signature' => $signature
        ]);
        
        // Gửi request đến MoMo
        $result = $this->execPostRequest($endpoint, json_encode($data));
        return json_decode($result, true);
    }
    
    /**
     * Thực hiện request HTTP POST
     * 
     * @param string $url URL đích
     * @param string $data Dữ liệu JSON để gửi
     * @return string Kết quả từ server
     */
    public function execPostRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        // Execute post
        $result = curl_exec($ch);
        
        // Close connection
        curl_close($ch);
        
        return $result;
    }
    
    /**
     * Xác thực chữ ký từ MoMo gửi về
     * 
     * @param array $momoResponse Dữ liệu từ MoMo gửi về
     * @return bool Kết quả xác thực
     */
    public function verifySignature($momoResponse) {
        if (empty($momoResponse['signature'])) {
            return false;
        }
        
        $receivedSignature = $momoResponse['signature'];
        
        // Tạo chuỗi để xác thực
        $rawHash = "accessKey=" . $this->config['accessKey'] .
                  "&amount=" . $momoResponse['amount'] .
                  "&extraData=" . ($momoResponse['extraData'] ?? '') .
                  "&orderId=" . $momoResponse['orderId'] .
                  "&orderInfo=" . $momoResponse['orderInfo'] .
                  "&orderType=" . ($momoResponse['orderType'] ?? '') .
                  "&partnerCode=" . $this->config['partnerCode'] .
                  "&payType=" . ($momoResponse['payType'] ?? '') .
                  "&requestId=" . $momoResponse['requestId'] .
                  "&responseTime=" . $momoResponse['responseTime'] .
                  "&resultCode=" . $momoResponse['resultCode'] .
                  "&transId=" . $momoResponse['transId'];
        
        $calculatedSignature = hash_hmac('sha256', $rawHash, $this->config['secretKey']);
        
        return $receivedSignature === $calculatedSignature;
    }
}