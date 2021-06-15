<?php
session_start();

// initializing variables
$fname = "";
$lastname = "";
$email = "";
$PhoneNumber = "";
$pick_up_date = "";
$pick_up_time = "";
$pick_up_loc = "";
$drop_date = "";
$drop_loc = "";
$drop_time = "";
$address = "";
$choosen_truck = "";
$good_type = "";
$errors = array();
$user_id = "";
$availability = array();
$timeslot = array();

// connect to the database
$db = mysqli_connect('localhost', 'root', '', 'cargo_management');

// REGISTER USER
if (isset($_POST['reg_user']))
{
    // receive all input values from the form
    $fname = mysqli_real_escape_string($db, $_POST['fname']);
    $lastname = mysqli_real_escape_string($db, $_POST['lastname']);
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $PhoneNumber = mysqli_real_escape_string($db, $_POST['PhoneNumber']);
    $password_1 = mysqli_real_escape_string($db, $_POST['password_1']);
    $password_2 = mysqli_real_escape_string($db, $_POST['password_2']);

    // form validation: ensure that the form is correctly filled ...
    // by adding (array_push()) corresponding error unto $errors array
    if (empty($fname))
    {
        array_push($errors, "firstname is required");
    }
    if (empty($lastname))
    {
        array_push($errors, "lastname is required");
    }
    if (empty($email))
    {
        array_push($errors, "Email is required");
    }
    if (empty($PhoneNumber))
    {
        array_push($errors, "Phone number is required");
    }
    if (empty($password_1))
    {
        array_push($errors, "Password is required");
    }
    if ($password_1 != $password_2)
    {
        array_push($errors, "The two passwords do not match");
    }

    $email_name = explode('@', $email);
    $user_id = $email_name[0] . substr($PhoneNumber, 0, 5);

    // first check the database to make sure
    // a user does not already exist with the same username and/or email
    $user_check_query = "SELECT * FROM user WHERE email='$email'";
    $result = mysqli_query($db, $user_check_query);
    $user = mysqli_fetch_assoc($result);

    if ($user)
    { // if user exists
        array_push($errors, "Email already exists");
    }
    $user_check_query = "SELECT * FROM user WHERE phone_number='$PhoneNumber' ";
    $result = mysqli_query($db, $user_check_query);
    $user = mysqli_fetch_assoc($result);

    if ($user)
    { // if user exists
        array_push($errors, "Phone Number already exists");
    }

    // Finally, register user if there are no errors in the form
    if (count($errors) == 0)
    {
        $password = ($password_1); //encrypt the password before saving in the database
        $query = "INSERT INTO user (user_id,first_name,last_name,email,phone_number,password)
  			  VALUES('$user_id','$fname','$lastname','$email','$PhoneNumber','$password')";
        #echo $query;
        mysqli_query($db, $query);
        header('location: login.php');
    }
}

// LOGIN USER
if (isset($_POST['login_user']))
{
    $email = mysqli_real_escape_string($db, $_POST['email']);
    $password = mysqli_real_escape_string($db, $_POST['password']);

    if (empty($email))
    {
        array_push($errors, "Email_ID is required");
    }
    if (empty($password))
    {
        array_push($errors, "Password is required");
    }

    if (count($errors) == 0)
    {
        $query = "SELECT * FROM user WHERE email='$email' AND password='$password'";
        $results = mysqli_query($db, $query);
        $user = mysqli_fetch_assoc($results);
        if (mysqli_num_rows($results) == 1)
        {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['first_name'];
            $_SESSION['success'] = "You are now logged in";
            header('location: welcome.php');
        }
        else
        {
            array_push($errors, "Wrong username/password combination");
        }
    }
}

//placing an Order
if (isset($_POST['place_order']))
{
    // receive all input values from the form
    $pick_up_loc = mysqli_real_escape_string($db, $_POST['city_pick_up']);
    $_SESSION['pick_up_loc'] = $pick_up_loc;
    $drop_loc = mysqli_real_escape_string($db, $_POST['drop_city']);
    $_SESSION['drop_loc'] = $drop_loc;
    $good_type = mysqli_real_escape_string($db, $_POST['good_type']);
    $_SESSION['good_type'] = $good_type;
    $choosen_truck = mysqli_real_escape_string($db, $_POST['truck']);
    $_SESSION['choosen_truck'] = $choosen_truck;
    $pick_up_date = mysqli_real_escape_string($db, $_POST['pick_up_date']);
    $_SESSION['pick_up_date'] = $pick_up_date;
    $drop_date = mysqli_real_escape_string($db, $_POST['drop_date']);
    $_SESSION['drop_date'] = $drop_date;
    $preferred_time = mysqli_real_escape_string($db, $_POST['pick_up_time']);
    $_SESSION['preferred_time'] = $preferred_time;
    // form validation: ensure that the form is correctly filled ...
    // by adding (array_push()) corresponding error unto $errors array
    if (empty($pick_up_loc))
    {
        array_push($errors, "pickup location is required");
    }
    elseif (empty($drop_loc))
    {
        array_push($errors, "drop location is required");
    }
    elseif (empty($choosen_truck))
    {
        array_push($errors, "truck type is required");
    }
    elseif (empty($pick_up_date))
    {
        array_push($errors, "pickup date is required");
    }
    elseif (empty($drop_date))
    {
        array_push($errors, "drop date is required");
    }
    elseif (empty($preferred_time))
    {
        array_push($errors, "preferred_time is required");
    }
    elseif ($drop_date < $pick_up_date)
    {
        array_push($errors, "The dates are invalid");
    }

    // first check the database to make sure
    // a user does not already exist with the same username and/or email
    else
    {
        //checking AVAILABLITY
        // run query
        $query = mysqli_query($db, "SELECT * FROM trucks where veh_type='$choosen_truck'");
        // set array
        $truck_array = array();
        // look through query
        while ($row = mysqli_fetch_assoc($query))
        {
            // add each row returned into an array
            $truck_array[] = $row['veh_reg'];
        }
        foreach ($truck_array as $truck_no)
        {

            $query = "SELECT * from booking where truck_id= '$truck_no'";
            $result = mysqli_query($db, $query);
            $user = mysqli_fetch_assoc($result);
            if (mysqli_num_rows($result) == 0)
            {
                $query = "SELECT * from trucks where veh_reg='$truck_no'";
                $result = mysqli_query($db, $query);
                if (mysqli_num_rows($result) == 1)
                {
                    $no = array();
                    $no[] = $truck_no;
                    $no[] = $preferred_time;
                    $availability[] = $no;
                }
            }
            else
            {

                /**$query = "SELECT * FROM booking where truck_id='$truck_no'and end_date>'$pick_up_date'";
                $result = mysqli_query($db, $query);
                $user = mysqli_fetch_assoc($result);
                if (mysqli_num_rows($result) == 0)
                {
                    $no = array();
                    $no[] = $truck_no;
                    $no[] = $preferred_time;
                    $availability[] = $no;
                }***/
                $query= "SELECT * FROM `booking` WHERE truck_id='$truck_no' and start_date>'$pick_up_date' and start_date<'$drop_date'";
                $result = mysqli_query($db, $query);
                $user = mysqli_fetch_assoc($result);
                if (mysqli_num_rows($result) == 0)
                {echo "yesh ";
                  $query= "SELECT * FROM `booking` WHERE truck_id='$truck_no' and end_date>'$pick_up_date' and end_date<'$drop_date'";
                  $result = mysqli_query($db, $query);
                  $user = mysqli_fetch_assoc($result);
                  if (mysqli_num_rows($result) == 0)
                  {echo "yesh ";
                    $query= "SELECT * FROM `booking` WHERE truck_id='$truck_no' and start_date<'$pick_up_date' and end_date>'$drop_date'";
                    $result = mysqli_query($db, $query);
                    $user = mysqli_fetch_assoc($result);
                    if (mysqli_num_rows($result) == 0)
                    {echo "yesh ";
                      $query= "SELECT * FROM `booking` WHERE truck_id='$truck_no' and start_date>'$pick_up_date' and end_date<'$drop_date'";
                      $result = mysqli_query($db, $query);
                      $user = mysqli_fetch_assoc($result);
                      if (mysqli_num_rows($result) == 0)
                      {
                        $query= "SELECT * FROM `booking` WHERE truck_id='$truck_no' and start_date='$pick_up_date'";
                        $result = mysqli_query($db, $query);
                        $user = mysqli_fetch_assoc($result);
                        if (mysqli_num_rows($result) == 0)
                        {
                          $query= "SELECT * FROM `booking` WHERE truck_id='$truck_no' and end_date='$pick_up_date'";
                          $result = mysqli_query($db, $query);
                          $user = mysqli_fetch_assoc($result);
                          if (mysqli_num_rows($result) == 0)
                          {
                        $no = array();
                        $no[] = $truck_no;
                        $no[] = $preferred_time;
                        $availability[] = $no;
                        echo "yesh ";
                      }
                      }
                      }
                    }
                  }
                }

                $query = "SELECT * FROM booking where truck_id='$truck_no'and end_date='$pick_up_date'";
                $result = mysqli_query($db, $query);
                $user = mysqli_fetch_assoc($result);
                if (mysqli_num_rows($result) == 1)
                {
                    $cmp1 = $user['drop_time'];
                    $cmp2 = $_SESSION['preferred_time'];
                    $cmpt1 = strtotime($cmp1);
                    $cmpt2 = strtotime($cmp2);
                    if ($cmpt2 < $cmpt1)
                    {
                        $no = array();
                        $no[] = $truck_no;
                        $no[] = $user['drop_time'];
                        $availability[] = $no;
                    }
                }

            }

        }
        foreach ($availability as $avail)
        {
            $timeslot[] = $avail[1];
        }
        if (!empty($timeslot))
        {
            $_SESSION['timeslot'] = $availability;
            header('location:truckavailability.php');

        }
        else
        {
           header('location:notruckavailable.php');
        }
    }
}

//choosing truck
if (isset($_POST['check_price']))
{
    $slots = mysqli_real_escape_string($db, $_POST['available_time']);
    $pick_up_time = substr($slots, 11, 15);
    $vehicle_id = substr($slots, 0, 9);
    $drop_time = mysqli_real_escape_string($db, $_POST['drop_time']);
    $address = mysqli_real_escape_string($db, $_POST['location_address']);
    if (empty($slots))
    {
        array_push($errors, "choose an available truck");
    }
    if (empty($drop_time))
    {
        array_push($errors, "drop time is required");
    }
    if (empty($address))
    {
        array_push($errors, "pick up point is required");
    }
    //header('location:price.php');

}

//inserting bookingdetails into database
if (isset($_POST['check_price']))
{
    $result = mysqli_query($db, "SELECT MAX(booking_id) AS maximum FROM booking");
    $row = mysqli_fetch_assoc($result);
    $booking_id = $row['maximum'] + 1;

    //printing to check if its entered properly
    echo $_SESSION['user_id'];
    echo "<br>";
    echo $booking_id;
    echo "<br>";
    echo $_SESSION['pick_up_date'];
    echo "<br>";
    echo $_SESSION['drop_date'];
    echo "<br>";
    echo $pick_up_time;
    echo "<br>";
    echo $drop_time;
    echo "<br>";
    echo $_SESSION['good_type'];
    echo "<br>";
    echo $_SESSION['choosen_truck'];
    echo "<br>";
    echo $vehicle_id;
    echo "<br>";
    echo $address;
    echo "<br>";

    $uid = $_SESSION['user_id'];
    $pdate = $_SESSION['pick_up_date'];
    $ddate = $_SESSION['drop_date'];
    $gtype = $_SESSION['good_type'];
    $ctruck = $_SESSION['choosen_truck'];
    $picloc = $_SESSION['pick_up_loc'];
    $droploc = $_SESSION['drop_loc'];

    // Finally, inserting all the details inside in the database after confirmation
    $query = "INSERT INTO booking(user_id,booking_id,start_date,end_date,pick_up_time,drop_time,pick_up_location,drop_location,goods_type,truck,truck_id,pick_up_point)VALUES($uid,$booking_id,'$pdate','$ddate','$pick_up_time','$drop_time','$picloc','$droploc','$gtype','$ctruck','$vehicle_id','$address')";
    echo $query;
    //$query = "INSERT INTO booking (drop_time,booking_id,user_id)
    //VALUES('$drop_time','$booking_id','$_SESSION['user_id']')";
    mysqli_query($db, $query);
}

?>
