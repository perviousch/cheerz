<!DOCTYPE html>
<html>
<head>
    <title>Aspect Ratio Calculator</title>
</head>
<body>
    <h1>Aspect Ratio Calculator</h1>
    <form method="post" action="">
        <label for="width">Original Width:</label>
        <input type="number" id="width" name="width" required><br><br>

        <label for="height">Original Height:</label>
        <input type="number" id="height" name="height" required><br><br>

        <label for="maxWidth">Max Width:</label>
        <input type="number" id="maxWidth" name="maxWidth" required><br><br>

        <label for="maxHeight">Max Height:</label>
        <input type="number" id="maxHeight" name="maxHeight" required><br><br>

        <input type="submit" name="submit" value="Calculate">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $width = $_POST['width'];
        $height = $_POST['height'];
        $maxWidth = $_POST['maxWidth'];
        $maxHeight = $_POST['maxHeight'];

        function scaleDimensions($width, $height, $maxWidth, $maxHeight) {
            // Calculate the aspect ratio
            $aspectRatio = $width / $height;

            // Determine the new dimensions within the constraints
            if ($width > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = $maxWidth / $aspectRatio;
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            if ($newHeight > $maxHeight) {
                $newHeight = $maxHeight;
                $newWidth = $maxHeight * $aspectRatio;
            }

            return array('width' => round($newWidth, 2), 'height' => round($newHeight, 2));
        }

        $result = scaleDimensions($width, $height, $maxWidth, $maxHeight);
        echo "<h2>Scaled Dimensions</h2>";
        echo "Original Dimensions: {$width} x {$height}<br>";
        echo "Scaled Dimensions: {$result['width']} x {$result['height']}";
    }
    ?>
</body>
</html>
