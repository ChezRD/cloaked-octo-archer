<?php $INC_DIR = $_SERVER["DOCUMENT_ROOT"] . "/includes";?>

<?php require_once "$INC_DIR/header.php"; ?>

    <!-- Custom page content -->

    <div class="row">
        <div class="col-lg-10 col-lg-offset-1 top-margin-index-logo">
            <?php $xcrud = Xcrud::get_instance(); ?>
            <?php $xcrud->table('dial_results'); ?>
            <?php $xcrud->unset_add(); ?>
            <?php echo $xcrud->render(); ?>
        </div>
    </div>

<?php require_once "$INC_DIR/footer.php";