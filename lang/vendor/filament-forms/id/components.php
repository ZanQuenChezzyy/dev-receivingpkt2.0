<?php

return [
  'builder' => 
  [
    'actions' => 
    [
      'clone' => 
      [
        'label' => 'Duplikat',
      ],
      'add' => 
      [
        'label' => 'Tambahkan :label',
        'modal' => 
        [
          'heading' => 'Tambah ke :label',
          'actions' => 
          [
            'add' => 
            [
              'label' => 'Tambah',
            ],
          ],
        ],
      ],
      'add_between' => 
      [
        'label' => 'Sisipkan',
        'modal' => 
        [
          'heading' => 'Tambah ke :label',
          'actions' => 
          [
            'add' => 
            [
              'label' => 'Tambah',
            ],
          ],
        ],
      ],
      'delete' => 
      [
        'label' => 'Hapus',
      ],
      'edit' => 
      [
        'label' => 'Ubah',
        'modal' => 
        [
          'heading' => 'Ubah blok',
          'actions' => 
          [
            'save' => 
            [
              'label' => 'Simpan perubahan',
            ],
          ],
        ],
      ],
      'reorder' => 
      [
        'label' => 'Pindahkan',
      ],
      'move_down' => 
      [
        'label' => 'Turunkan',
      ],
      'move_up' => 
      [
        'label' => 'Naikkan',
      ],
      'collapse' => 
      [
        'label' => 'Sembunyikan',
      ],
      'expand' => 
      [
        'label' => 'Tampilkan',
      ],
      'collapse_all' => 
      [
        'label' => 'Sembunyikan semua',
      ],
      'expand_all' => 
      [
        'label' => 'Tampilkan semua',
      ],
    ],
  ],
  'checkbox_list' => 
  [
    'actions' => 
    [
      'deselect_all' => 
      [
        'label' => 'Batalkan semua pilihan',
      ],
      'select_all' => 
      [
        'label' => 'Pilih semua',
      ],
    ],
  ],
  'file_upload' => 
  [
    'editor' => 
    [
      'actions' => 
      [
        'cancel' => 
        [
          'label' => 'Batal',
        ],
        'drag_crop' => 
        [
          'label' => 'Mode "potong"',
        ],
        'drag_move' => 
        [
          'label' => 'Mode "geser"',
        ],
        'flip_horizontal' => 
        [
          'label' => 'Balik gambar secara horizontal',
        ],
        'flip_vertical' => 
        [
          'label' => 'Balik gambar secara vertikal',
        ],
        'move_down' => 
        [
          'label' => 'Geser gambar ke bawah',
        ],
        'move_left' => 
        [
          'label' => 'Geser gambar ke kiri',
        ],
        'move_right' => 
        [
          'label' => 'Geser gambar ke kanan',
        ],
        'move_up' => 
        [
          'label' => 'Geser gambar ke atas',
        ],
        'reset' => 
        [
          'label' => 'Kembalikan',
        ],
        'rotate_left' => 
        [
          'label' => 'Putar gambar ke kiri',
        ],
        'rotate_right' => 
        [
          'label' => 'Putar gambar ke kanan',
        ],
        'set_aspect_ratio' => 
        [
          'label' => 'Tentukan aspek rasio ke :ratio',
        ],
        'save' => 
        [
          'label' => 'Simpan',
        ],
        'zoom_100' => 
        [
          'label' => 'Perbesar ke 100%',
        ],
        'zoom_in' => 
        [
          'label' => 'Perbesar',
        ],
        'zoom_out' => 
        [
          'label' => 'Perkecil',
        ],
      ],
      'fields' => 
      [
        'height' => 
        [
          'label' => 'Tinggi',
          'unit' => 'px',
        ],
        'rotation' => 
        [
          'label' => 'Putar',
          'unit' => 'derajat',
        ],
        'width' => 
        [
          'label' => 'Lebar',
          'unit' => 'px',
        ],
        'x_position' => 
        [
          'label' => 'X',
          'unit' => 'px',
        ],
        'y_position' => 
        [
          'label' => 'Y',
          'unit' => 'px',
        ],
      ],
      'aspect_ratios' => 
      [
        'label' => 'Aspek rasio',
        'no_fixed' => 
        [
          'label' => 'Bebas',
        ],
      ],
      'svg' => 
      [
        'messages' => 
        [
          'confirmation' => 'Mengedit file SVG tidak disarankan karena dapat mengakibatkan penurunan kualitas saat melakukan penskalaan.\\n Apakah Anda yakin ingin melanjutkan?',
          'disabled' => 'Pengeditan file SVG dinonaktifkan karena dapat mengakibatkan penurunan kualitas saat melakukan penskalaan.',
        ],
      ],
    ],
  ],
  'key_value' => 
  [
    'actions' => 
    [
      'add' => 
      [
        'label' => 'Tambahkan baris',
      ],
      'delete' => 
      [
        'label' => 'Hapus baris',
      ],
      'reorder' => 
      [
        'label' => 'Ubah urutan baris',
      ],
    ],
    'fields' => 
    [
      'key' => 
      [
        'label' => 'Kunci',
      ],
      'value' => 
      [
        'label' => 'Nilai',
      ],
    ],
  ],
  'markdown_editor' => 
  [
    'toolbar_buttons' => 
    [
      'attach_files' => 'Lampirkan berkas',
      'blockquote' => 'Kutipan',
      'bold' => 'Tebal',
      'bullet_list' => 'Daftar',
      'code_block' => 'Kode',
      'heading' => 'Judul',
      'italic' => 'Miring',
      'link' => 'Tautan',
      'ordered_list' => 'Daftar berurut',
      'redo' => 'Kembalikan',
      'strike' => 'Coret',
      'table' => 'Table',
      'undo' => 'Batalkan',
    ],
    'file_attachments_accepted_file_types_message' => 'Uploaded files must be of type: :values.',
    'file_attachments_max_size_message' => 'Uploaded files must not be greater than :max kilobytes.',
    'tools' => 
    [
      'attach_files' => 'Attach files',
      'blockquote' => 'Blockquote',
      'bold' => 'Bold',
      'bullet_list' => 'Bullet list',
      'code_block' => 'Code block',
      'heading' => 'Heading',
      'italic' => 'Italic',
      'link' => 'Link',
      'ordered_list' => 'Numbered list',
      'redo' => 'Redo',
      'strike' => 'Strikethrough',
      'table' => 'Table',
      'undo' => 'Undo',
    ],
  ],
  'radio' => 
  [
    'boolean' => 
    [
      'true' => 'Ya',
      'false' => 'Tidak',
    ],
  ],
  'repeater' => 
  [
    'actions' => 
    [
      'add' => 
      [
        'label' => 'Tambahkan :label',
      ],
      'add_between' => 
      [
        'label' => 'Sisipkan',
      ],
      'delete' => 
      [
        'label' => 'Hapus',
      ],
      'clone' => 
      [
        'label' => 'Duplikat',
      ],
      'reorder' => 
      [
        'label' => 'Pindahkan',
      ],
      'move_down' => 
      [
        'label' => 'Turunkan',
      ],
      'move_up' => 
      [
        'label' => 'Naikkan',
      ],
      'collapse' => 
      [
        'label' => 'Sembunyikan',
      ],
      'expand' => 
      [
        'label' => 'Tampilkan',
      ],
      'collapse_all' => 
      [
        'label' => 'Sembunyikan semua',
      ],
      'expand_all' => 
      [
        'label' => 'Tampilkan semua',
      ],
    ],
  ],
  'rich_editor' => 
  [
    'dialogs' => 
    [
      'link' => 
      [
        'actions' => 
        [
          'link' => 'Buat tautan',
          'unlink' => 'Batalkan tautan',
        ],
        'label' => 'Tautan',
        'placeholder' => 'Masukkan tautan',
      ],
    ],
    'toolbar_buttons' => 
    [
      'attach_files' => 'Lampirkan berkas',
      'blockquote' => 'Kutipan',
      'bold' => 'Tebal',
      'bullet_list' => 'Daftar',
      'code_block' => 'Kode',
      'h1' => 'Judul',
      'h2' => 'Sub judul',
      'h3' => 'Anak judul',
      'italic' => 'Miring',
      'link' => 'Tautan',
      'ordered_list' => 'Daftar berurut',
      'redo' => 'Kembalikan',
      'strike' => 'Coret',
      'underline' => 'Garis bawah',
      'undo' => 'Batalkan',
    ],
    'actions' => 
    [
      'attach_files' => 
      [
        'label' => 'Upload file',
        'modal' => 
        [
          'heading' => 'Upload file',
          'form' => 
          [
            'file' => 
            [
              'label' => 
              [
                'new' => 'File',
                'existing' => 'Replace file',
              ],
            ],
            'alt' => 
            [
              'label' => 
              [
                'new' => 'Alt text',
                'existing' => 'Change alt text',
              ],
            ],
          ],
        ],
      ],
      'custom_block' => 
      [
        'modal' => 
        [
          'actions' => 
          [
            'insert' => 
            [
              'label' => 'Insert',
            ],
            'save' => 
            [
              'label' => 'Save',
            ],
          ],
        ],
      ],
      'grid' => 
      [
        'label' => 'Grid',
        'modal' => 
        [
          'heading' => 'Grid',
          'form' => 
          [
            'preset' => 
            [
              'label' => 'Preset',
              'placeholder' => 'None',
              'options' => 
              [
                'two' => 'Two',
                'three' => 'Three',
                'four' => 'Four',
                'five' => 'Five',
                'two_start_third' => 'Two (Start Third)',
                'two_end_third' => 'Two (End Third)',
                'two_start_fourth' => 'Two (Start Fourth)',
                'two_end_fourth' => 'Two (End Fourth)',
              ],
            ],
            'columns' => 
            [
              'label' => 'Columns',
            ],
            'from_breakpoint' => 
            [
              'label' => 'From breakpoint',
              'options' => 
              [
                'default' => 'All',
                'sm' => 'Small',
                'md' => 'Medium',
                'lg' => 'Large',
                'xl' => 'Extra large',
                '2xl' => 'Two extra large',
              ],
            ],
            'is_asymmetric' => 
            [
              'label' => 'Two asymmetric columns',
            ],
            'start_span' => 
            [
              'label' => 'Start span',
            ],
            'end_span' => 
            [
              'label' => 'End span',
            ],
          ],
        ],
      ],
      'link' => 
      [
        'label' => 'Link',
        'modal' => 
        [
          'heading' => 'Link',
          'form' => 
          [
            'url' => 
            [
              'label' => 'URL',
            ],
            'should_open_in_new_tab' => 
            [
              'label' => 'Open in new tab',
            ],
          ],
        ],
      ],
      'text_color' => 
      [
        'label' => 'Text color',
        'modal' => 
        [
          'heading' => 'Text color',
          'form' => 
          [
            'color' => 
            [
              'label' => 'Color',
              'options' => 
              [
                'slate' => 'Slate',
                'gray' => 'Gray',
                'zinc' => 'Zinc',
                'neutral' => 'Neutral',
                'stone' => 'Stone',
                'mauve' => 'Mauve',
                'olive' => 'Olive',
                'mist' => 'Mist',
                'taupe' => 'Taupe',
                'red' => 'Red',
                'orange' => 'Orange',
                'amber' => 'Amber',
                'yellow' => 'Yellow',
                'lime' => 'Lime',
                'green' => 'Green',
                'emerald' => 'Emerald',
                'teal' => 'Teal',
                'cyan' => 'Cyan',
                'sky' => 'Sky',
                'blue' => 'Blue',
                'indigo' => 'Indigo',
                'violet' => 'Violet',
                'purple' => 'Purple',
                'fuchsia' => 'Fuchsia',
                'pink' => 'Pink',
                'rose' => 'Rose',
              ],
            ],
            'custom_color' => 
            [
              'label' => 'Custom color',
            ],
          ],
        ],
      ],
    ],
    'file_attachments_accepted_file_types_message' => 'Uploaded files must be of type: :values.',
    'file_attachments_max_size_message' => 'Uploaded files must not be greater than :max kilobytes.',
    'no_merge_tag_search_results_message' => 'No merge tag results.',
    'mentions' => 
    [
      'no_options_message' => 'No options available.',
      'no_search_results_message' => 'No results match your search.',
      'search_prompt' => 'Start typing to search...',
      'searching_message' => 'Searching...',
    ],
    'tools' => 
    [
      'align_center' => 'Align center',
      'align_end' => 'Align end',
      'align_justify' => 'Align justify',
      'align_start' => 'Align start',
      'attach_files' => 'Attach files',
      'blockquote' => 'Blockquote',
      'bold' => 'Bold',
      'bullet_list' => 'Bullet list',
      'clear_formatting' => 'Clear formatting',
      'code' => 'Code',
      'code_block' => 'Code block',
      'custom_blocks' => 'Blocks',
      'details' => 'Details',
      'h1' => 'Title',
      'h2' => 'Heading 2',
      'h3' => 'Heading 3',
      'h4' => 'Heading 4',
      'h5' => 'Heading 5',
      'h6' => 'Heading 6',
      'grid' => 'Grid',
      'grid_delete' => 'Delete grid',
      'highlight' => 'Highlight',
      'horizontal_rule' => 'Horizontal rule',
      'italic' => 'Italic',
      'lead' => 'Lead text',
      'link' => 'Link',
      'merge_tags' => 'Merge tags',
      'ordered_list' => 'Numbered list',
      'paragraph' => 'Paragraph',
      'redo' => 'Redo',
      'small' => 'Small text',
      'strike' => 'Strikethrough',
      'subscript' => 'Subscript',
      'superscript' => 'Superscript',
      'table' => 'Table',
      'table_delete' => 'Delete table',
      'table_add_column_before' => 'Add column before',
      'table_add_column_after' => 'Add column after',
      'table_delete_column' => 'Delete column',
      'table_add_row_before' => 'Add row above',
      'table_add_row_after' => 'Add row below',
      'table_delete_row' => 'Delete row',
      'table_merge_cells' => 'Merge cells',
      'table_split_cell' => 'Split cell',
      'table_toggle_header_row' => 'Toggle header row',
      'table_toggle_header_cell' => 'Toggle header cell',
      'text_color' => 'Text color',
      'underline' => 'Underline',
      'undo' => 'Undo',
    ],
    'uploading_file_message' => 'Uploading file...',
  ],
  'select' => 
  [
    'actions' => 
    [
      'create_option' => 
      [
        'modal' => 
        [
          'heading' => 'Buat',
          'actions' => 
          [
            'create' => 
            [
              'label' => 'Buat',
            ],
            'create_another' => 
            [
              'label' => 'Buat & buat lainnya',
            ],
          ],
        ],
        'label' => 'Create',
      ],
      'edit_option' => 
      [
        'modal' => 
        [
          'heading' => 'Ubah',
          'actions' => 
          [
            'save' => 
            [
              'label' => 'Simpan',
            ],
          ],
        ],
        'label' => 'Edit',
      ],
    ],
    'boolean' => 
    [
      'true' => 'Ya',
      'false' => 'Tidak',
    ],
    'loading_message' => 'Memuat...',
    'max_items_message' => 'Hanya :count yang dapat dipilih.',
    'no_search_results_message' => 'Tidak ada hasil yang sesuai dengan pencarian Anda.',
    'placeholder' => 'Pilih salah satu opsi',
    'searching_message' => 'Sedang mencari...',
    'search_prompt' => 'Ketik untuk mencari...',
    'no_options_message' => 'No options available.',
  ],
  'tags_input' => 
  [
    'placeholder' => 'Tag baru',
    'actions' => 
    [
      'delete' => 
      [
        'label' => 'Delete',
      ],
    ],
  ],
  'text_input' => 
  [
    'actions' => 
    [
      'hide_password' => 
      [
        'label' => 'Sembunyikan kata sandi',
      ],
      'show_password' => 
      [
        'label' => 'Tampilkan kata sandi',
      ],
      'copy' => 
      [
        'label' => 'Copy',
        'message' => 'Copied',
      ],
    ],
  ],
  'toggle_buttons' => 
  [
    'boolean' => 
    [
      'true' => 'Ya',
      'false' => 'Tidak',
    ],
  ],
  'wizard' => 
  [
    'actions' => 
    [
      'previous_step' => 
      [
        'label' => 'Sebelumnya',
      ],
      'next_step' => 
      [
        'label' => 'Selanjutnya',
      ],
    ],
  ],
  'modal_table_select' => 
  [
    'actions' => 
    [
      'select' => 
      [
        'label' => 'Select',
        'actions' => 
        [
          'select' => 
          [
            'label' => 'Select',
          ],
        ],
      ],
    ],
  ],
];
