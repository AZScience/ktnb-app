<?php $c = curl_init('https://lcms.ntt.edu.vn/login/token.php?username=a&password=b&service=moodle_mobile_app'); curl_setopt($c, CURLOPT_RETURNTRANSFER, true); echo curl_exec($c);
