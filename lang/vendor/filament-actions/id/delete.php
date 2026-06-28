<?php

return [
  'single' => 
  [
    'label' => 'Hapus',
    'modal' => 
    [
      'heading' => 'Hapus :label',
      'actions' => 
      [
        'delete' => 
        [
          'label' => 'Hapus',
        ],
      ],
    ],
    'notifications' => 
    [
      'deleted' => 
      [
        'title' => 'Data berhasil dihapus',
      ],
    ],
  ],
  'multiple' => 
  [
    'label' => 'Hapus yang dipilih',
    'modal' => 
    [
      'heading' => 'Hapus :label yang dipilih',
      'actions' => 
      [
        'delete' => 
        [
          'label' => 'Hapus yang dipilih',
        ],
      ],
    ],
    'notifications' => 
    [
      'deleted' => 
      [
        'title' => 'Data berhasil dihapus',
      ],
      'deleted_partial' => 
      [
        'title' => 'Deleted :count of :total',
        'missing_authorization_failure_message' => 'You don\'t have permission to delete :count.',
        'missing_processing_failure_message' => ':count could not be deleted.',
      ],
      'deleted_none' => 
      [
        'title' => 'Failed to delete',
        'missing_authorization_failure_message' => 'You don\'t have permission to delete :count.',
        'missing_processing_failure_message' => ':count could not be deleted.',
      ],
    ],
  ],
];
