<?php
$qs = array(
	"Strategic" => 
		array(
			'queues' => array('es_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
			'filter_out' => array('team'=>'/^MC\-.*/'),
		),

	"Strategic Linux" =>
		array(
			'queues' => array('es_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
			'filter_out' => array(
					'tech'	=>	'/windows/i',
					'platform' =>	'/windows/i',
					'team' =>	'/^MC\-.*/'
				),
		),

	"Strategic Windows" =>
		array(
			'queues' => array('es_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
			'filter_out' => array(
					'tech' =>	'/linux/i',
					'platform' =>	'/linux/i',
					'team' =>	'/^MC\-.*/'
				),
		),

	"Strategic (AfterHours)" => 
		array(
			'queues' => array('es_ua', 'esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
			'filter_out' => array(),
		),

	"Strategic (AfterHours) - Linux" =>
                array(
                       	'queues' => array('es_ua', 'esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
			'filter_out' => array(
                                        'tech'  =>	'/windows/i',
                                        'platform' =>   '/windows/i'
                  	        ),
               	),

	"Strategic (AfterHours) - Windows" =>
                array(
                       	'queues' => array('es_ua', 'esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
			'filter_out' => array(
                                       	'tech' =>	'/linux/i',
                                       	'platform' =>   '/linux/i'
                               	),
               	),

        "Strategic - Omega" =>
                array(
                      	'queues' => array('es_ua', 'esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_in' => array(
                                'team' => '/.*C21.*/i',
                               	'zdtags' => '/.*omega_team.*/'
                        ),
                       	'filter_out' => array(),
                       	'modifier' => array()
               	),

        "Strategic - Alerts View (Linux)" =>
               	array(
                       	'queues' => array('es_ua', 'esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                       	'filter_in' => array(
				'category' => '/monitoring_alerts.*/i',
				'zdtags' => '/.*monitoring_alert.*/'
			),
                       	'filter_out' => array(
				'tech' => '/windows/i',
				'platform' => '/windows/i'
			)
                ),

     	"Strategic - Low Priority Alerts (Linux)" =>
          	array(
                       	'queues' => array('es_ua', 'esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                       	'filter_in' => array(
                               	'category' => '/monitoring_alerts.*/i',
                               	'zdtags' => '/.*monitoring_alert.*/'
                        ),
                       	'filter_out' => array(
                               	'tech' => '/windows/i',
                               	'platform' => '/windows/i',
				'subject' => '/.*(sitescope|rackspace monitoring event|Rackwatch).*/i',
                       	)
                ),

        "Strategic - Low Priority Alerts (Windows)" =>
                array(
                      	'queues' => array('es_ua', 'esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_in' => array(
                               	'category' => '/monitoring_alerts.*/i',
                                'zdtags' => '/.*monitoring_alert.*/'
                       	),
                       	'filter_out' => array(
                               	'tech' => '/linux/i',
                               	'platform' => '/linux/i',
                                'subject' => '/.*(sitescope|rackspace monitoring event|Rackwatch).*/i',
	                )
                ),

        "Corporate - Quick Response Team" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate', 'intensive_mon_ua_us', 'intensive_aric_ua'),
                        'filter_in' => array(
                                'category' => '/monitoring_alerts.*/i',
                                'zdtags' => '/.*monitoring_alert.*/',
                                'view' => '/.*intensive_mon_ua_us.*/i'
                        ),
                        'filter_out' => array(),
                        'modifier' => array(
                                array(
                                      	'index' => 0.8,
                                        'filter' => array(
                                                'view' => '/intensive_mon_ua_us/',
                                        ),
                                ),
                                array(
                                      	'index' => 2,
                                        'filter' => array(
                                                'subject' => '/.*(rackspace monitoring event|sitescope)*/i',
                                                'status' => '/.*(alert received|new).*/i'
                                        ),
                                ),
                                array(
                                      	'index' => 10,
                                        'filter' => array(
                                                'view' => '/intensive_aric_ua/i'
                                        )
                                ),
                        )
                ),

	"Corporate - Team B1" =>
               	array(
                       	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                       	'filter_out' => array(),
                        'modifier' => array(
				array(
	                               	'index' => 2,
        	                        'filter' => array(
                	                               	'team' => '/B1/'
                        	               	),
                               		),
				)
                ),

	"Corporate - Team B2" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	                                'team' => '/B2/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B3" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	                                'team' => '/B3/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B4" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	                                'team' => '/B4/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B5" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	               	       	       	'team' => '/B5/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B6" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	                                'team' => '/B6/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B7" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	               	       	       	'team' => '/B7/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B8" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	               	       	       	'team' => '/B8/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B9" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	               	       	       	'team' => '/B9/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B11" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
				array(
	                                'index' => 2,
        	                        'filter' => array(
                	               	       	       	'team' => '/B11/'
                        	                ),
                                	),
				)
                ),

	"Corporate - Team B13" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(),
                        'modifier' => array(
                                array(
					'index' => 2,
                                	'filter' => array(
                               	       		       	'team' => '/B13/'
                                        	),
                                	),
				)
                ),

	"Strategic - Team C1" =>
                array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_out' => array('team'=>'/^MC\-.*/'),
			'modifier' => array(
				array(
					'index' => 3,
					'filter' => array(
							'team' => '/C1/'
						),
					),
				)
                ),

        "Strategic - Team C2" =>
               	array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                       	'filter_out' => array('team'=>'/^MC\-.*/'),
                        'modifier' => array(
				array(
	                                'index' => 3,
        	                        'filter' => array(
                	                                'team' => '/C2/'
                        	                ),
                                	),
				)
                ),

        "Strategic - Team C3" =>
                array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_out' => array('team'=>'/^MC\-.*/'),
                        'modifier' => array(
				array(
	                                'index' => 3,
        	                        'filter' => array(
                	                                'team' => '/C3/'
                        	                ),
                                	),
				)
                ),

        "Strategic - Team C4" =>
                array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_out' => array('team'=>'/^MC\-.*/'),
                        'modifier' => array(
				array(
	                                'index' => 3,
        	                        'filter' => array(
                	                                'team' => '/C4/'
                        	                ),
                                	),
				)
                ),

	"Strategic - Team C5" =>
                array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_out' => array('team'=>'/^MC\-.*/'),
                        'modifier' => array(
				array(
	                                'index' => 3,
        	                        'filter' => array(
                	                                'team' => '/C5/'
                        	                ),
                                	),
				)
                ),

        "Strategic - Team C6" =>
                array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_out' => array('team'=>'/^MC\-.*/'),
                        'modifier' => array(
				array(
	                                'index' => 3,
        	                        'filter' => array(
                	                                'team' => '/C6/'
                        	                ),
                                	),
				)
                ),

        "Strategic - Team C7" =>
                array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_out' => array('team'=>'/^MC\-.*/'),
                        'modifier' => array(
				array(
	                                'index' => 3,
        	                        'filter' => array(
                	                                'team' => '/C7/'
                        	                ),
                                	),
				)
                ),

        "Strategic - Team C8" =>
               	array(
                       	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                       	'filter_out' => array('team'=>'/^MC\-.*/'),
                       	'modifier' => array(
				array(
	                               	'index' => 3,
        	                       	'filter' => array(
                	                               	'team' => '/C8/'
                        	                ),
                               		),
				)
               	),

        "Strategic - Team C8 (Linux)" =>
                array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_out' => array(
				'team' =>'/^MC\-.*/',
				'tech' => '/windows/i',
				'platform' => '/windows/i'
			),
                        'modifier' => array(
                                array(
                                       	'index' => 3,
                                       	'filter' => array(
                                                       	'team' => '/C8/'
                                               	),
                                       	),
                               	)
                ),

        "Strategic - Team C8 (Windows)" =>
                array(
                      	'queues' => array('esah_ua', 'cloud-hybrid-enterprise', 'cloud-cloudservers/coreservices', 'cloud-managedcloud'),
                        'filter_out' => array(
                                'team' =>'/^MC\-.*/',
                                'tech' => '/linux/i',
                                'platform' => '/linux/i'
                        ),
                        'modifier' => array(
                               	array(
                                       	'index' => 3,
                                        'filter' => array(
                                                        'team' => '/C8/'
                                                ),
                                        ),
                                )
                ),

	"Corporate" => 
		array(
			'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
			'filter_out' => array(
					'team' =>	'/B0/'
				),
		),

        "Corporate Linux" =>
               	array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                       	'filter_out' => array(
                                       	'team' =>	'/B0/',
					'tech' =>	'/windows/i',
					'platform' =>	'/windows/i'
                               	),

                ),

        "Corporate Windows" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-corporate'),
                        'filter_out' => array(
                                        'team' =>	'/B0/',
                                        'tech' =>	'/linux/i',
                                        'platform' =>	'/linux/i'
	                        ),

                ),

        "Enterprise All" =>
                array(
                      	//'queues' => array('corpus_ua', 'cloud-hybrid-corporate', 'es_ua', 'cloud-hybrid-enterprise'),
			'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('team' => '/(ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

	"Enterprise All - Watchlist" =>
                array(
                      	//'queues' => array('corpus_ua', 'cloud-hybrid-corporate', 'es_ua', 'cloud-hybrid-enterprise'),
                        'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('team' => '/(ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i', 'OnWatch' => '/0/'),
                ),


        "Enterprise All (Linux)" =>
                array(
			'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                       	'filter_out' => array(
					'tech' => '/windows/i',
					'platform' => '/windows/i',
					'team' => '/(ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i',
					'subject' => '/^ALERT:OPSMGR:.*/'
                               	),

                ),

        "Enterprise All (Linux) Testing" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array(
                                       	'tech' => '/windows/i',
                                       	'platform' => '/windows/i',
                                        'team' => '/(ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i',
                                        'subject' => '/^ALERT:OPSMGR:.*/'
                               	),

                ),

        "Enterprise All (Windows)" =>
                array(
			'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                       	'filter_out' => array(
					'tech' => '/linux/i',
					'platform' => '/linux/i',
					'team' => '/(ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'
                               	),

                ),

       "Enterprise Latin America" =>
                array(
                       	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_out' => array('team' => '/((ENT|RPC)(E|W|F|Z).*|Int.*)/i'),
                ),

       "Enterprise East" =>
                array(
                      	//'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua'),
			'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('team' => '/(ENTW|ENTF|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Enterprise East (Linux)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/windows/i', 'platform' => '/windows/i', 'team' => '/(ENTW|ENTF|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Enterprise East (Windows)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/linux/i', 'platform' => '/linux/i', 'team' => '/(ENTW|ENTF|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Enterprise West" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('team' => '/(ENTE|ENTF|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Enterprise West (Linux)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/windows/i', 'platform' => '/windows/i', 'team' => '/(ENTE|ENTF|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Enterprise West (Windows)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/linux/i', 'platform' => '/linux/i', 'team' => '/(ENTE|ENTF|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Enterprise F1000" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('team' => '/(ENTE|ENTW|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Enterprise F1000 (Linux)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/windows/i', 'platform' => '/windows/i', 'team' => '/(ENTE|ENTW|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Enterprise F1000 (Windows)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/linux/i', 'platform' => '/linux/i', 'team' => '/(ENTE|ENTW|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|INT).*/i'),
                ),

        "Team N" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^(ENT|UT).*/i'),
                        'filter_out' => array(
                                'tech' => '/windows/i', 'platform' => '/windows/i',
                                'team' => '/(ENTE-1WIN|ENTE-5WIN|ENTE-6MIX|ENTE-7LIN|ENTE-8WIN|ENTF-1MIX|ENTF-2MIX|ENTF-3MIX|ENTF-4MIX|ENTF-5MIX|ENTW-2LIN|ENTW-3MIX|ENTW-5WIN|ENTW-6WIN|ENTW-7MIX|ENTW-8MC|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
                        )
                ),

        "Team N (Priority)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/windows/i', 'platform' => '/windows/i'),
                        'modifier' => array(
                                array(
                                      	'index' => 2,
                                        'filter' => array('team' => '/(ENTE-2LIN|ENTE-4MIX|ENTW-4LIN|ENTE-3WIN|ENTW-1WIN|ENTZ-1MIX|ENTZ-7MIX)/'),
                                ))
                ),

        "Team O" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^(ENT|UT).*/i'),
                        'filter_out' => array(
                                'tech' => '/windows/i', 'platform' => '/windows/i',
                                'team' => '/(ENTE-1WIN|ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-5WIN|ENTE-6MIX|ENTE-7LIN|ENTE-8WIN|ENTF-5MIX|ENTW-1WIN|ENTW-2LIN|ENTW-3MIX|ENTW-4LIN|ENTW-5WIN|ENTW-6WIN|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*|ENTW-8MC)/'
	                )
                ),

        "Team O (Priority)" =>
                array(
                        'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/windows/i', 'platform' => '/windows/i'),
                       	'modifier' => array(
                               	array(
                                       	'index' => 2,
                                       	'filter' => array('team' => '/(ENTW-7MIX|ENTF-1MIX|ENTF-2MIX|ENTW-8MC|ENTF-3MIX|ENTF-4MIX)/'),
	                        ))
                ),


        "Team P" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			//'filter_in' => array('team' => '/^(ENT.*|$)/i'),
                        'filter_out' => array(
				'tech' => '/windows/i', 'platform' => '/windows/i',
				'team' => '/(ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-6MIX|ENTF-1MIX|ENTF-2MIX|ENTF-3MIX|ENTF-4MIX|ENTF-5MIX|ENTW-1WIN|ENTW-3MIX|ENTW-4LIN|ENTW-7MIX|ENTW-8MC|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/',
				'subject' => '/^ALERT:OPSMGR:.*/'
			)
                ),

        "Team P (Priority)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array(
				'tech' => '/windows/i', 
				'platform' => '/windows/i',
				'team'	=>	'/(ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*|ENT.-\dMC)/'
			),
                        'modifier' => array(
                                array(
                                      	'index' => 2,
                                        'filter' => array('team' => '/(ENTW-2LIN|ENTE-7LIN|ENTE-1WIN|ENTE-5WIN|ENTE-8WIN|ENTW-5WIN|ENTW-6WIN)/'),
                                ))
                ),

        "Team Q" =>
                array(
                       	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^ENT.*/i'),
                       	'filter_out' => array(
                               	'tech' => '/linux/i', 'platform' => '/linux/i',
                               	'team' => '/(ENTE-1WIN|ENTE-5WIN|ENTE-6MIX|ENTE-7LIN|ENTE-8WIN|ENTF-1MIX|ENTF-2MIX|ENTF-3MIX|ENTF-4MIX|ENTF-5MIX|ENTW-2LIN|ENTW-3MIX|ENTW-5WIN|ENTW-6WIN|ENTW-7MIX|ENTW-8MC|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
	                )
                ),

        "Team Q (Priority)" =>
                array(
                        'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/linux/i', 'platform' => '/linux/i'),
                        'modifier' => array(
                                array(
                                      	'index' => 2,
                                        'filter' => array('team' => '/(ENTE-3WIN|ENTW-1WIN|ENTE-2LIN|ENTE-4MIX|ENTW-4LIN|ENTZ-1MIX|ENTZ-7MIX)/'),
                                ))
                ),

        "Team R" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^ENT.*/i'),
                        'filter_out' => array(
                                'tech' => '/linux/i', 'platform' => '/linux/i',
                                'team' => '/(ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-6MIX|ENTE-7LIN|ENTF-1MIX|ENTF-2MIX|ENTF-3MIX|ENTF-4MIX|ENTF-5MIX|ENTW-1WIN|ENTW-2LIN|ENTW-3MIX|ENTW-4LIN|ENTW-5WIN|ENTW-6WIN|ENTW-7MIX|ENTW-8MC|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
                        )
                ),

        "Team R (Priority)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/linux/i', 'platform' => '/linux/i'),
                        'modifier' => array(
                                array(
                                      	'index' => 2,
                                        'filter' => array('team' => '/(ENTE-1WIN|ENTE-5WIN|ENTE-8WIN)/'),
                                ))
                ),

        "Team S" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^ENT.*/i'),
                        'filter_out' => array(
                                'tech' => '/linux/i', 'platform' => '/linux/i',
                                'team' => '/(ENTE-1WIN|ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-5WIN|ENTE-6MIX|ENTE-7LIN|ENTE-8WIN|ENTF-3MIX|ENTF-4MIX|ENTF-5MIX|ENTW-1WIN|ENTW-2LIN|ENTW-3MIX|ENTW-4LIN|ENTW-8MC|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
                        )
                ),

        "Team S (Priority)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/linux/i', 'platform' => '/linux/i'),
                        'modifier' => array(
                                array(
                                      	'index' => 2,
                                        'filter' => array('team' => '/(ENTW-7MIX|ENTF-1MIX|ENTF-2MIX|ENTW-5WIN|ENTW-6WIN)/'),
                                ))
                ),

        "Team T" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^ENT.*/i'),
                        'filter_out' => array(
                                'tech' => '/linux/i', 'platform' => '/linux/i',
                                'team' => '/(ENTE-1WIN|ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-5WIN|ENTE-6MIX|ENTE-8WIN|ENTF-1MIX|ENTF-2MIX|ENTF-5MIX|ENTW-1WIN|ENTW-3MIX|ENTW-4LIN|ENTW-5WIN|ENTW-6WIN|ENTW-7MIX|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTW-8MC|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
                        )
                ),

        "Team T (Priority)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/linux/i', 'platform' => '/linux/i'),
                        'modifier' => array(
                                array(
                                      	'index' => 2,
                                        'filter' => array('team' => '/(ENTW-2LIN|ENTE-7LIN|ENTW-8MC|ENTF-3MIX|ENTF-4MIX)/'),
                                ))
                ),

        "Team U" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^(ENT|UT).*/i'),
                        'filter_out' => array(
				'tech' => '/linux/i', 'platform' => '/linux/i',
                                'team' => '/(ENTE-1WIN|ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-5WIN|ENTE-7LIN|ENTE-8WIN|ENTF-1MIX|ENTF-2MIX|ENTF-3MIX|ENTF-4MIX|ENTW-1WIN|ENTW-2LIN|ENTW-4LIN|ENTW-5WIN|ENTW-6WIN|ENTW-7MIX|ENTW-8MC|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
                        )
                ),

        "Team U - Windows" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^ENT.*/i'),
                        'filter_out' => array(
                               	'tech' => '/linux/i', 'platform' => '/linux/i',
                               	'team' => '/(ENTE-1WIN|ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-5WIN|ENTE-7LIN|ENTE-8WIN|ENTF-1MIX|ENTF-2MIX|ENTF-3MIX|ENTF-4MIX|ENTW-1WIN|ENTW-2LIN|ENTW-4LIN|ENTW-5WIN|ENTW-6WIN|ENTW-7MIX|ENTW-8MC|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
	                )
                ),

        "Team U - Linux" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
			'filter_in' => array('team' => '/^ENT.*/i'),
                        'filter_out' => array(
                               	'tech' => '/windows/i', 'platform' => '/windows/i',
                               	'team' => '/(ENTE-1WIN|ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-5WIN|ENTE-7LIN|ENTE-8WIN|ENTF-1MIX|ENTF-2MIX|ENTF-3MIX|ENTF-4MIX|ENTW-1WIN|ENTW-2LIN|ENTW-4LIN|ENTW-5WIN|ENTW-6WIN|ENTW-7MIX|ENTW-8MC|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
	                )
                ),

	"Team V" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_in' => array('team' => '/^ENT.*/i'),
                        'filter_out' => array(
                                'tech' => '/windows/i', 'platform' => '/windows/i',
                               	'team' => '/(ENTE-1WIN|ENTE-2LIN|ENTE-3WIN|ENTE-4MIX|ENTE-5WIN|ENTE-7LIN|ENTE-8WIN|ENTF-1MIX|ENTF-2MIX|ENTF-3MIX|ENTF-4MIX|ENTW-1WIN|ENTW-2LIN|ENTW-4LIN|ENTW-5WIN|ENTW-6WIN|ENTW-7MIX|ENTW-8MC|ENTZ-1MIX|ENTZ-7MIX|ENTZ-8MIX|RPCZ-3MIX|ENTZ-2MIX|Int.*|ENT.-0...$|LATM.*)/'
	                )
                ),

        "Team U (Priority)" =>
                array(
                      	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        'filter_out' => array('tech' => '/linux/i', 'platform' => '/linux/i'),
                        'modifier' => array(
                                array(
                                      	'index' => 2,
                                        'filter' => array('team' => '/(ENTF-5MIX|ENTE-6MIX|ENTW-3MIX)/'),
                                ))
                ),

       "Enterprise Cloud Only" =>
               	array(
                       	'queues' => array('cloud-hybrid-enterprise','cloud-hybrid-corporate'),
                       	'filter_out' => array(),
                ),

	"Enterprise ARIC Only" => 
		array(
			'queues' => array('intensive_aric_ua'),
			'filter_out' => array(
				'team' => '/(RPC.*|ENTZ-8MIX|ENTZ-2MIX)/'
			),
		),

       "Enterprise Implementation" =>
                array(
                       	'queues' => array('corpus_ua', 'cloud-hybrid-enterprise', 'esah_ua', 'cloud-hybrid-corporate', 'intensive_aric_ua', 'intensive_mon_ua'),
                        //'filter_out' => array('team' => '/((ENT|RPC)(E|W|F|Z).*|Int.*)/i'),
			'filter_in' => array(
				'team'	=>	'/^ENT.-0...$/'
			),
                ),
	"Storage - DAS" =>
                array(
                      	'queues' => array('storage_das'),
                        'filter_out' => array(),
                ),
        "Storage - NAS" =>
               	array(
                       	'queues' => array('storage_nas'),
                       	'filter_out' => array(),
                ),
	"Storage - SAN" =>
                array(
                        'queues' => array('storage_san'),
                        'filter_out' => array('status'=>'/.*Scheduled.*/'),
                ),
	"DBA - MySQL" =>
                array(
                      	'queues' => array('dba_mysql_ua'),
                       	'filter_out' => array(),
                ),
	"DBA - MSSQL" =>
               	array(
                      	'queues' => array('dba_mssql_ua'),
                       	'filter_out' => array(),
                ),
	"DBA - Oracle" =>
                array(
                      	'queues' => array('dba_oracle_ua'),
                        'filter_out' => array(),
	        ),
	"Critical Sites" =>
                array(
                      	'queues' => array('critical_sites'),
                        'filter_out' => array(),
	        ),
	"Virtualization" =>
                array(
                      	'queues' => array('virt'),
                        'filter_out' => array(),
	        ),
	"Enterprise NetSec" =>
                array(
                       	'queues' => array('netsec_ua'),
                       	'filter_out' => array(),
                ),
        "Segment Support" =>
                array(
                      	'queues' => array('segment_support'),
                        'filter_out' =>	array(),
                ),

);
?>
