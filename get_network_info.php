<?php
session_start();
require_once 'config.php';
header('Content-Type: application/json');

class IPDetector {
    private $office_ranges = [];
    
    public function __construct($ranges) {
        $this->office_ranges = $ranges;
    }
    
    public function isIPv6($ip) {
        return strpos($ip, ':') !== false;
    }
    
    public function isInIPv4Range($ip, $range) {
        list($subnet, $bits) = explode('/', $range);
        
        // For single IP addresses (/32), do direct comparison
        if ($bits === '32') {
            return $ip === $subnet;
        }
        
        // Convert IP addresses to binary strings for comparison
        $ip_binary = sprintf("%032b", ip2long($ip));
        $subnet_binary = sprintf("%032b", ip2long($subnet));
        
        // Compare only the first n bits
        return substr($ip_binary, 0, $bits) === substr($subnet_binary, 0, $bits);
    }
    
    public function isInIPv6Range($ip, $range) {
        list($subnet, $bits) = explode('/', $range);
        
        // Convert IP address to binary format
        $addr = inet_pton($ip);
        $subnet = inet_pton($subnet);
        
        if (!$addr || !$subnet) {
            return false;
        }
        
        // Compare the relevant bits
        $addr_bin = '';
        $subnet_bin = '';
        
        for ($i = 0; $i < strlen($addr); $i++) {
            $addr_bin .= sprintf("%08b", ord($addr[$i]));
            $subnet_bin .= sprintf("%08b", ord($subnet[$i]));
        }
        
        return substr($addr_bin, 0, $bits) === substr($subnet_bin, 0, $bits);
    }
    
    public function isOfficeIP($ip) {
        foreach ($this->office_ranges as $range) {
            // Skip invalid ranges
            if (strpos($range, '/') === false) {
                continue;
            }
            
            // Check if the range is IPv6 or IPv4
            $isIPv6Range = strpos($range, ':') !== false;
            $isIPv6Address = $this->isIPv6($ip);
            
            // If IP version doesn't match range version, skip
            if ($isIPv6Range !== $isIPv6Address) {
                continue;
            }
            
            // Check appropriate version
            if ($isIPv6Range) {
                if ($this->isInIPv6Range($ip, $range)) {
                    return true;
                }
            } else {
                if ($this->isInIPv4Range($ip, $range)) {
                    return true;
                }
            }
        }
        return false;
    }
}

// Define your office IP ranges (both IPv4 and IPv6)
$OFFICE_IP_RANGES = [
    '152.58.0.0/16',          // Your office network (152.58.*.*)
    '192.168.31.0/24',        // Local IPv4 subnet
    '182.48.226.166/32',      // Previous office public IPv4
    '2409:40c0:23b:a2ee::/64',// Office IPv6 subnet
    'fe80::/10'               // Link-local IPv6
];

// Get client IP, checking various possible sources
function getClientIP() {
    $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }
    
    return $_SERVER['REMOTE_ADDR'];
}

// Initialize detector and check IP
$detector = new IPDetector($OFFICE_IP_RANGES);
$client_ip = getClientIP();
$isOfficeNetwork = $detector->isOfficeIP($client_ip);

// Return result
echo json_encode([
    'isOfficeNetwork' => $isOfficeNetwork,
    'clientIP' => $client_ip,
    'debug' => [
        'isIPv6' => $detector->isIPv6($client_ip),
        'headers' => array_intersect_key($_SERVER, array_flip(['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR']))
    ]
], JSON_PRETTY_PRINT);