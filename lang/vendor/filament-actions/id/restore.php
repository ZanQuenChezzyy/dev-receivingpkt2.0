<?php

return [
  'single' => 
  [
    'label' => 'Kembalikan data',
    'modal' => 
    [
      'heading' => 'Kembalikan :label',
      'actions' => 
      [
        'restore' => 
        [
          'label' => 'Kembalikan',
        ],
      ],
    ],
    'notifications' => 
    [
      'restored' => 
      [
        'title' => 'Data berhasil dikembalikan',
      ],
    ],
  ],
  'multiple' => 
  [
    'label' => 'Kembalikan data yang dipilih',
    'modal' => 
    [
      'heading' => 'Kembalikan :label yang dipilih',
      'actions' => 
      [
        'restore' => 
        [
          'label' => 'Kembalikan',
        ],
      ],
    ],
    'notifications' => 
    [
      'restored' => 
      [
        'title' => 'Data berhasil dikembalikan',
      ],
      'restored_partial' => 
      [
        'title' => 'Restored :count of :total',
        'missing_authorization_failure_message' => 'You don\'t have permission to restore :count.',
        'missing_processing_failure_message' => ':count could not be restored.',
      ],
      'restored_none' => 
      [
        'title' => 'Failed to restore',
        'missing_authorization_failure_message' => 'You don\'t have permission to restore :count.',
        'missing_processing_failure_message' => ':count could not be restored.',
      ],
    ],
  ],
];
