<div class="content-wrapper">
    <section class="content-header">
        <h1><?php echo $title; ?></h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Educational Game System Setup</h3>
                    </div>
                    
                    <div class="box-body">
                        <?php if (isset($is_installed) && $is_installed): ?>
                            <div class="alert alert-success">
                                <i class="fa fa-check"></i> <strong>System Installed!</strong> 
                                The Educational Game System is already installed and ready to use.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>Quick Access</h4>
                                    <p><a href="<?php echo base_url('gamebuilder'); ?>" class="btn btn-primary">
                                        <i class="fa fa-gamepad"></i> Manage Games
                                    </a></p>
                                    <p><a href="<?php echo base_url('gamebuilder/dashboard'); ?>" class="btn btn-info">
                                        <i class="fa fa-dashboard"></i> Analytics Dashboard
                                    </a></p>
                                </div>
                                <div class="col-md-6">
                                    <h4>Maintenance</h4>
                                    <p><a href="<?php echo base_url('admin/gamesetup/uninstall'); ?>" class="btn btn-danger">
                                        <i class="fa fa-trash"></i> Uninstall System
                                    </a></p>
                                </div>
                            </div>
                            
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> <strong>System Not Installed</strong>
                                The Educational Game System needs to be installed before use.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>Installation Requirements</h4>
                                    <ul>
                                        <li>CodeIgniter 3.x framework</li>
                                        <li>MySQL database with CREATE privileges</li>
                                        <li>Existing permission system tables</li>
                                        <li>Classes and sections management</li>
                                    </ul>
                                    
                                    <div class="well">
                                        <h5>What will be installed:</h5>
                                        <ul>
                                            <li><strong>Database Tables:</strong> educational_games, game_results, student_points</li>
                                            <li><strong>Permissions:</strong> Game Builder and Student Games permission groups</li>
                                            <li><strong>Indexes:</strong> Performance optimization indexes</li>
                                        </ul>
                                    </div>
                                    
                                    <p>
                                        <a href="<?php echo base_url('admin/gamesetup/install'); ?>" class="btn btn-success btn-lg">
                                            <i class="fa fa-download"></i> Install Game System
                                        </a>
                                    </p>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-green"><i class="fa fa-gamepad"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Game Types</span>
                                            <span class="info-box-number">2</span>
                                            <span class="progress-description">Quiz & Matching Games</span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-box">
                                        <span class="info-box-icon bg-blue"><i class="fa fa-users"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">User Roles</span>
                                            <span class="info-box-number">3</span>
                                            <span class="progress-description">Admin, Teacher, Student</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($installation_status)): ?>
                            <hr>
                            <h4>System Status</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Database Tables</h5>
                                    <ul class="list-unstyled">
                                        <?php foreach ($installation_status['tables'] as $table => $exists): ?>
                                            <li>
                                                <i class="fa fa-<?php echo $exists ? 'check text-green' : 'times text-red'; ?>"></i>
                                                <?php echo $table; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h5>Permissions</h5>
                                    <ul class="list-unstyled">
                                        <?php foreach ($installation_status['permissions'] as $permission => $exists): ?>
                                            <li>
                                                <i class="fa fa-<?php echo $exists ? 'check text-green' : 'times text-red'; ?>"></i>
                                                <?php echo str_replace('_', ' ', $permission); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>