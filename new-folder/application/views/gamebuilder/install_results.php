<div class="content-wrapper">
    <section class="content-header">
        <h1><?php echo $title; ?></h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Installation Results</h3>
                    </div>
                    
                    <div class="box-body">
                        <?php if ($results['overall_success']): ?>
                            <div class="alert alert-success">
                                <h4><i class="fa fa-check"></i> Installation Successful!</h4>
                                The Educational Game System has been installed successfully.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h4><i class="fa fa-times"></i> Installation Failed!</h4>
                                There were errors during the installation process.
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Successful</span>
                                        <span class="info-box-number"><?php echo $results['success']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-red"><i class="fa fa-times"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Errors</span>
                                        <span class="info-box-number"><?php echo $results['errors']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-blue"><i class="fa fa-info"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Messages</span>
                                        <span class="info-box-number"><?php echo count($results['messages']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h4>Installation Log</h4>
                        <div class="well" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($results['messages'] as $message): ?>
                                <p><?php echo $message; ?></p>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($results['overall_success']): ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <h4>Next Steps</h4>
                                    <ol>
                                        <li>Go to <strong>Admin → Roles</strong> to assign game permissions to user roles</li>
                                        <li>Assign <strong>"Game Builder"</strong> permissions to Teacher/Staff roles</li>
                                        <li>Assign <strong>"Student Games"</strong> permissions to Student role</li>
                                        <li>Start creating games at <a href="<?php echo base_url('gamebuilder'); ?>">Game Management</a></li>
                                    </ol>
                                    
                                    <p class="text-center">
                                        <a href="<?php echo base_url('gamebuilder'); ?>" class="btn btn-primary btn-lg">
                                            <i class="fa fa-gamepad"></i> Start Using Games
                                        </a>
                                        <a href="<?php echo base_url('admin/gamesetup'); ?>" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Back to Setup
                                        </a>
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-center">
                                <a href="<?php echo base_url('admin/gamesetup'); ?>" class="btn btn-primary">
                                    <i class="fa fa-arrow-left"></i> Back to Setup
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>