<?php
require_once('config.php');

//1. Fetch Property Given Id
//2. Insert New Property
//3. Fetch Reviews given PropertyId
//4. Insert Review

// connect to the database
function connectDB(){
    $mysqli = mysqli_connect(MYSQL_HOST, MYSQL_USER,MYSQL_PW,MYSQL_DB, MYSQL_PORT);
    if (mysqli_connect_errno($mysqli)) {
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
			echo "Failed to connect to MySQL: " . mysqli_connect_error();
				die;
	}else{
        return $mysqli;
    }
}

function selectAllProperties()
{
	$mysqli = connectDB();
	$stmt = $mysqli->prepare("SELECT * FROM property;");
	if(!$stmt->execute()) {
		//echo "error executing query" . $stmt->error;
		return array('status'=>'FAIL', 'msg'=>"error executing query" . $stmt->error);
	} else {
		$stmt->bind_result($pk, $address, $description,$beds, $baths, $manager, $city, $state, $zip, $propertyType, $name, $occupancy, $aptNumber);
		$propertyList = array();
		while($stmt->fetch()) {
			$property = array('id'=>$pk, 'address'=>$address, 'beds'=>$beds, 'baths'=>$baths, 'manager'=>$manager, 'city'=>$city, 'state'=>$state, 
						'zip'=>$zip, 'propertyType'=>$propertyType, 'name'=>$name, 'occupancy'=>$occupancy, 'aptNumber'=>$aptNumber);
			array_push($propertyList, $property);
			
			//Can use for debugging. Remove before release.
			//echo $pk . ", " . $address . ", " . $description;
		}
		
		//Extra check to protect against unintended side effects
		if (count($propertyList) == 0) {
			return array('status'=>'FAIL', 'msg'=>"No Properties Found");
		} else {
			return array('status'=>'OK', 'msg'=>'Success', 'properties'=>$propertyList);
		}
	}
}

// Fetch Property Given Id
function findPropertyById($id){
    $conn = connectDB();
    $sql = "SELECT * FROM property WHERE pk=?";
 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if(!$stmt->execute()) {
        echo "error executing query" . $stmt->error;
        $arr = array('status'=>'FAIL', 'msg'=>"error executing query" . $stmt->error);
    }else{
        $stmt->bind_result($pk, $address, $description, $beds, $baths, $managerName, 
        $city, $state, $zipCode, $propertyType, $name, $occupancy, $apt_number);
        $stmt->fetch();
        if(isset($pk)) {
            $status = 'OK';
            $msg = 'SUCCESS';
            $arr = array('status'=>$status, 'msg'=>$msg, 'address'=>$address, 
            'description'=>$description,'beds'=>$beds,'baths'=>$baths,'managerName'=>$managerName,
            'city'=>$city,'state'=>$state,'zipCode'=>$zipCode,'propertyType'=>$propertyType,
            'name'=>$name,'occupancy'=>$occupancy,'apt_number'=>$apt_number);
        } else {
            $status = 'FAIL';
            $msg = 'PROPERTY NOT FOUND';
            $arr = array('status'=>$status, 'msg'=>$msg);
        }
    }
    $stmt->close();
    $conn->close();
    return $arr;
}

// Fetch Reviews given PropertyId
function findReviewsById($propertyID){
    $conn = connectDB();
    $sql = "SELECT * FROM review WHERE propertyID=?";
 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $propertyID);

    if(!$stmt->execute()) {
        echo "error executing query" . $stmt->error;
        $arr = array('status'=>'FAIL', 'msg'=>"error executing query" . $stmt->error);
    }else{
        $stmt->bind_result($pk, $recommended, $rent, $maintenance, $neighborhood, $body, 
        $propertyID);
		$reviewList = array();
        while($stmt->fetch()) {
			$review = array('id'=>$pk, 'recommended'=>$recommended, 'rent'=>$rent, 'maintenance'=>$maintenance, 
				'neighborhood'=>$neighborhood, 'body'=>$body, 'propertyID'=>$propertyID);
			array_push($reviewList, $review);
		}
		
        if(count($reviewList) > 0) {
            $status = 'OK';
            $msg = 'SUCCESS';
            $arr = array('status'=>$status, 'msg'=>$msg, 'reviews'=>$reviewList);
        } else {
            $status = 'FAIL';
            $msg = 'REVIEWS NOT FOUND';
            $arr = array('status'=>$status, 'msg'=>$msg);
        }
    }
    $stmt->close();
    $conn->close();
    return $arr;
}

// Insert New Property
function addProperty($address, $description, $beds, $baths, $managerName, 
$city, $state, $zipCode, $propertyType, $name, $occupancy, $apt_number){
    $conn = connectDB();
    $sql = "INSERT INTO property (address, description, beds, baths, managerName,
    city,state,zipCode,propertyType,name,occupancy,apt_number) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiissssssii", $address, $description, $beds, $baths,
    $managerName, $city, $state, $zipCode, $propertyType, $name, $occupancy, $apt_number);

    if(!$stmt->execute()) {
        echo "FAILED TO INSERT PROPERTIES" . $stmt->error;
        $arr = array('status'=>'FAIL', 'msg'=>"FAILED TO INSERT PROPERTIES" . $stmt->error);
        file_put_contents("log2.txt", "PostProperty Failure " . $stmt->error);
    }else{
        $status = 'OK';
		$msg = 'SUCCESS';
		$arr = array('status'=>$status, 'msg'=>$msg);
        file_put_contents("log2.txt", "PostProperty Success");
    }
    $stmt->close();
    $conn->close();
    return $arr;
}

// Insert Review
function addReview($recommended, $rent, $maintenance, $neighborhood, $body, $propertyID){
    $conn = connectDB();
    $sql = "INSERT INTO review (recommended, rent, maintenance, neighborhood, body, propertyID) 
    VALUES (?,?,?,?,?,?)";
	$temp = (int) $propertyID;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssi", $recommended, $rent, $maintenance, $neighborhood, $body, $temp);
	$arr = array();
    if(!$stmt->execute()) {
        echo "FAILED TO INSERT REVIEW" . $stmt->error;
        $arr = array('status'=>'FAIL', 'msg'=>"FAILED TO INSERT REVIEW" . $stmt->error);
    }else{
        $status = 'OK';
		$msg = 'SUCCESS';
		$arr = array('status'=>$status, 'msg'=>$msg);
    }
    $stmt->close();
    $conn->close();
    return $arr;
}

// Search by properties name or address
function keywordSearch($keyword){
    $conn = connectDB();

    $keyword = strtoupper($keyword);    
    $keyword = strip_tags($keyword);    
    $keyword = trim($keyword); 

    $sql = "SELECT * FROM property WHERE upper(name) LIKE ? OR upper(address) LIKE ?";
 
    $temp = '%'.$keyword.'%';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $temp, $temp);
    $arr = array();

    if(!$stmt->execute()) {
        echo "error executing query" . $stmt->error;
        $arr = array('status'=>'FAIL', 'msg'=>"error executing query" . $stmt->error);
    }else{
        $stmt->bind_result($pk, $address, $description, $beds, $baths, $managerName, 
        $city, $state, $zipCode, $propertyType, $name, $occupancy, $apt_number);

		$propertyList = array();
        while($stmt->fetch()) {
			$property = array('id'=>$pk, 'address'=>$address, 'beds'=>$beds, 'baths'=>$baths, 'manager'=>$managerName, 'city'=>$city, 'state'=>$state, 
						'zip'=>$zipCode, 'propertyType'=>$propertyType, 'name'=>$name, 'occupancy'=>$occupancy, 'aptNumber'=>$apt_number);
			array_push($propertyList, $property);
        }
    }

        $anymatches= mysqli_stmt_num_rows($stmt);
        if($anymatches == 0){
            $status = 'FAIL';
            $msg = 'PROPERTY NOT FOUND';
            $arr = array('status'=>$status, 'msg'=>$msg);
        }else{
            $arr = array('status'=>'OK', 'msg'=>'SUCCESS', 'count'=>$anymatches, 'properties'=>$propertyList);
        }
 
    $stmt->close();
    $conn->close();
    return $arr;
}

function selectPropertiesByCategory($type)
{
	$conn = connectDB();
	$sql = "SELECT * FROM property WHERE propertyType=?;";
	
	$stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $type);
	if(!$stmt->execute()) {
		//echo "error executing query" . $stmt->error;
		return array('status'=>'FAIL', 'msg'=>"error executing query" . $stmt->error);
	} else {
		$stmt->bind_result($pk, $address, $description,$beds, $baths, $manager, $city, $state, $zip, $propertyType, $name, $occupancy, $aptNumber);
		$propertyList = array();
		while($stmt->fetch()) {
			$property = array('id'=>$pk, 'address'=>$address, 'beds'=>$beds, 'baths'=>$baths, 'manager'=>$manager, 'city'=>$city, 'state'=>$state, 
						'zip'=>$zip, 'propertyType'=>$propertyType, 'name'=>$name, 'occupancy'=>$occupancy, 'aptNumber'=>$aptNumber);
			array_push($propertyList, $property);
			
			//Can use for debugging. Remove before release.
			//echo $pk . ", " . $address . ", " . $description;
		}
		
		//Extra check to protect against unintended side effects
		if (count($propertyList) == 0) {
			return array('status'=>'FAIL', 'msg'=>"No Properties Found");
		} else {
			return array('status'=>'OK', 'msg'=>'Success', 'properties'=>$propertyList);
		}
	}
}
        
?>