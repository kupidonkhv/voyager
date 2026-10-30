@extends('voyager::master')

@section('page_title', __('voyager::generic.'.($dataTypeContent->exists ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.($dataTypeContent->exists ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
    </h1>
@stop

@section('content')
    <div class="page-content container-fluid">
        <form class="form-edit-add" role="form"
              action="{{ $dataTypeContent->exists
                    ? route('voyager.'.$dataType->slug.'.update', $dataTypeContent->id)
                    : route('voyager.'.$dataType->slug.'.store') }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @if ($dataTypeContent->exists)
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-8">
                    <div class="panel panel-bordered">
                        <div class="panel-body">
                            @foreach ($dataTypeContent->exists ? $dataType->editRows : $dataType->addRows as $row)
                                <div class="form-group">
                                    <label for="{{ $row->field }}">{{ $row->getTranslatedAttribute('display_name') }}</label>

                                    @if ($row->field === 'dimensions')
                                        @php
                                            $options = (array) ($row->details->options ?? []);
                                            $current = old('dimensions', $dataTypeContent->dimensions ?? ($row->details->default ?? '400x300'));
                                        @endphp
                                        <select class="form-control" name="dimensions" id="dimensions">
                                            @foreach ($options as $value => $label)
                                                <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($row->field === 'description')
                                        <textarea class="form-control" name="description" id="description" rows="5">{{ old('description', $dataTypeContent->description ?? '') }}</textarea>
                                    @elseif ($row->field === 'sort_order')
                                        <input type="number" class="form-control" name="sort_order" id="sort_order"
                                               value="{{ old('sort_order', $dataTypeContent->sort_order ?? 0) }}">
                                    @elseif ($row->field === 'title')
                                        <input type="text" class="form-control" name="title" id="title" required
                                               value="{{ old('title', $dataTypeContent->title ?? '') }}">
                                    @elseif (!in_array($row->field, ['id', 'created_at', 'updated_at']))
                                        <input type="text" class="form-control" name="{{ $row->field }}"
                                               value="{{ old($row->field, $dataTypeContent->{$row->field} ?? '') }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="panel panel-bordered panel-primary">
                        <div class="panel-body">
                            <div class="form-group">
                                <label>{{ __('voyager::generic.save') }}</label>
                                <div class="buttons">
                                    <button type="submit" class="btn btn-primary save-btn">
                                        <i class="icon voyager-plus"></i> {{ __('voyager::generic.save') }}
                                    </button>
                                    <a href="{{ route('voyager.'.$dataType->slug.'.index') }}" class="btn btn-default">
                                        {{ __('voyager::generic.cancel') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if ($dataTypeContent->exists)
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-bordered panel-primary">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="icon voyager-photos"></i> {{ __('voyager::gallery.photos') }}</h3>
                            </div>
                            <div class="panel-body">
                                <div id="media-dropzone" class="gallery-dropzone" tabindex="0"
                                     data-upload-url="{{ route('voyager.galleries.upload-media', $dataTypeContent->id) }}">
                                    <input type="file" id="media-file-input" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden>
                                    <i class="icon voyager-upload"></i>
                                    <strong>{{ __('voyager::gallery.drop_files') }}</strong>
                                    <span>{{ __('voyager::gallery.file_hint', ['size' => round(config('voyager.gallery.max_upload_size', 10240) / 1024)]) }}</span>
                                </div>

                                <div id="media-grid" class="row"
                                     data-sort-url="{{ route('voyager.galleries.sort-media', $dataTypeContent->id) }}"
                                     data-description-url="{{ route('voyager.galleries.update-media-description', $dataTypeContent->id) }}"
                                     data-delete-url="{{ route('voyager.galleries.delete-media', $dataTypeContent->id) }}">
                                    @forelse ($dataTypeContent->images as $media)
                                        @php
                                            try {
                                                $thumb = $media->getUrl('thumb');
                                            } catch (\Throwable $e) {
                                                $thumb = $media->getUrl();
                                            }
                                        @endphp
                                        <div class="col-sm-4 col-md-3 media-item" data-media-id="{{ $media->id }}" draggable="true">
                                            <div class="thumbnail">
                                                <div class="media-preview">
                                                    <img src="{{ $thumb }}" alt="{{ $media->file_name }}">
                                                </div>
                                                <div class="media-info" title="{{ $media->file_name }}">
                                                    {{ Str::limit($media->file_name, 24) }}
                                                </div>
                                                <input type="text" class="form-control input-sm media-desc-input"
                                                       value="{{ $media->getCustomProperty('description', '') }}"
                                                       placeholder="{{ __('voyager::gallery.description_placeholder') }}">
                                                <button type="button" class="btn btn-xs btn-danger delete-media" title="{{ __('voyager::generic.delete') }}">
                                                    <i class="icon voyager-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-xs-12">
                                            <p class="text-center text-muted" id="no-media-msg">{{ __('voyager::gallery.empty') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-info">{{ __('voyager::gallery.save_first') }}</div>
            @endif
        </form>
    </div>

    @if ($dataTypeContent->exists)
        <div class="modal modal-danger fade" tabindex="-1" id="gallery_delete_modal" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::gallery.delete_confirm_title') }}</h4>
                    </div>
                    <div class="modal-body">
                        <p>{{ __('voyager::gallery.delete_confirm') }}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                        <button type="button" class="btn btn-danger" id="gallery_delete_btn">{{ __('voyager::generic.delete') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('javascript')
    @if ($dataTypeContent->exists)
        <style>
            .gallery-dropzone { align-items:center; background:#f8f9fa; border:2px dashed #337ab7; border-radius:5px; cursor:pointer; display:flex; flex-direction:column; gap:5px; justify-content:center; min-height:130px; padding:20px; text-align:center; }
            .gallery-dropzone.is-dragging { background:#eaf4fb; border-color:#23527c; }
            .gallery-dropzone i { color:#337ab7; font-size:28px; }
            .gallery-dropzone span { color:#777; font-size:12px; }
            .media-item .thumbnail { border:2px solid transparent; margin-bottom:15px; padding:6px; position:relative; }
            .media-item .thumbnail:hover { border-color:#337ab7; }
            .media-item.is-dragging { opacity:.45; }
            .media-item .media-preview { align-items:center; background:#f2f2f2; border-radius:4px; display:flex; height:130px; justify-content:center; overflow:hidden; }
            .media-item .media-preview img { max-height:130px; max-width:100%; object-fit:cover; }
            .media-item .media-info { overflow:hidden; padding:6px 0; text-overflow:ellipsis; white-space:nowrap; }
            .media-item .delete-media { border-radius:50%; display:none; height:28px; line-height:28px; padding:0; position:absolute; right:8px; top:8px; width:28px; }
            .media-item .thumbnail:hover .delete-media { display:block; }
        </style>

        <script>
            (function () {
                var dropzone = document.getElementById('media-dropzone');
                var input = document.getElementById('media-file-input');
                var grid = document.getElementById('media-grid');
                var modal = window.jQuery ? window.jQuery('#gallery_delete_modal') : null;
                var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                var uploadUrl = dropzone.dataset.uploadUrl;
                var sortUrl = grid.dataset.sortUrl;
                var descriptionUrl = grid.dataset.descriptionUrl;
                var deleteUrl = grid.dataset.deleteUrl;
                var pendingDelete = null;

                function request(url, data) {
                    return fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: data
                    }).then(function (response) {
                        return response.json().then(function (payload) {
                            if (!response.ok) {
                                throw new Error(payload.message || 'Request failed');
                            }
                            return payload;
                        });
                    });
                }

                function showEmptyState() {
                    var empty = document.getElementById('no-media-msg');
                    if (empty) {
                        empty.style.display = grid.querySelector('.media-item') ? 'none' : '';
                    }
                }

                function makeItem(media) {
                    var item = document.createElement('div');
                    item.className = 'col-sm-4 col-md-3 media-item';
                    item.dataset.mediaId = media.id;
                    item.draggable = true;

                    var thumbnail = document.createElement('div');
                    thumbnail.className = 'thumbnail';

                    var preview = document.createElement('div');
                    preview.className = 'media-preview';
                    var image = document.createElement('img');
                    image.src = media.thumb || media.url;
                    image.alt = media.name;
                    preview.appendChild(image);

                    var info = document.createElement('div');
                    info.className = 'media-info';
                    info.title = media.name;
                    info.textContent = media.name.length > 24 ? media.name.substring(0, 24) + '…' : media.name;

                    var description = document.createElement('input');
                    description.type = 'text';
                    description.className = 'form-control input-sm media-desc-input';
                    description.value = media.description || '';
                    description.placeholder = @json(__('voyager::gallery.description_placeholder'));

                    var remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'btn btn-xs btn-danger delete-media';
                    remove.title = @json(__('voyager::generic.delete'));
                    remove.innerHTML = '<i class="icon voyager-trash"></i>';

                    thumbnail.appendChild(preview);
                    thumbnail.appendChild(info);
                    thumbnail.appendChild(description);
                    thumbnail.appendChild(remove);
                    item.appendChild(thumbnail);

                    return item;
                }

                function uploadFile(file) {
                    if (!file || !file.type.match(/^image\//)) {
                        window.toastr && toastr.error(@json(__('voyager::gallery.upload_error')));
                        return;
                    }

                    dropzone.classList.add('is-dragging');
                    var data = new FormData();
                    data.append('file', file);

                    request(uploadUrl, data).then(function (payload) {
                        if (!payload.success) {
                            throw new Error(payload.message || 'Upload failed');
                        }
                        grid.appendChild(makeItem(payload.media));
                        showEmptyState();
                        window.toastr && toastr.success(@json(__('voyager::gallery.uploaded')));
                    }).catch(function (error) {
                        window.toastr && toastr.error(error.message || @json(__('voyager::gallery.upload_error')));
                    }).finally(function () {
                        dropzone.classList.remove('is-dragging');
                    });
                }

                function saveOrder() {
                    var data = new FormData();
                    grid.querySelectorAll('.media-item').forEach(function (item) {
                        data.append('media_ids[]', item.dataset.mediaId);
                    });
                    request(sortUrl, data).catch(function () {
                        window.toastr && toastr.error(@json(__('voyager::gallery.sort_error')));
                    });
                }

                function saveDescription(input) {
                    var value = input.value;
                    if (input.dataset.lastValue === value) {
                        return;
                    }
                    input.dataset.lastValue = value;

                    var data = new FormData();
                    data.append('media_id', input.closest('.media-item').dataset.mediaId);
                    data.append('description', value);

                    request(descriptionUrl, data).then(function () {
                        window.toastr && toastr.success(@json(__('voyager::gallery.description_saved')));
                    }).catch(function () {
                        window.toastr && toastr.error(@json(__('voyager::gallery.description_error')));
                    });
                }

                dropzone.addEventListener('click', function () { input.click(); });
                dropzone.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        input.click();
                    }
                });
                input.addEventListener('change', function () {
                    Array.prototype.forEach.call(input.files, uploadFile);
                    input.value = '';
                });

                ['dragenter', 'dragover'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        dropzone.classList.add('is-dragging');
                    });
                });
                ['dragleave', 'drop'].forEach(function (eventName) {
                    dropzone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        dropzone.classList.remove('is-dragging');
                    });
                });
                dropzone.addEventListener('drop', function (event) {
                    Array.prototype.forEach.call(event.dataTransfer.files, uploadFile);
                });

                grid.addEventListener('click', function (event) {
                    var button = event.target.closest('.delete-media');
                    if (!button) {
                        return;
                    }

                    pendingDelete = button.closest('.media-item');
                    if (modal && modal.length) {
                        modal.modal('show');
                    } else if (window.confirm(@json(__('voyager::gallery.delete_confirm')))) {
                        deletePending();
                    }
                });

                function deletePending() {
                    if (!pendingDelete) {
                        return;
                    }

                    var data = new FormData();
                    data.append('media_id', pendingDelete.dataset.mediaId);

                    request(deleteUrl, data).then(function () {
                        pendingDelete.remove();
                        pendingDelete = null;
                        showEmptyState();
                    }).catch(function (error) {
                        window.toastr && toastr.error(error.message || @json(__('voyager::gallery.delete_error')));
                    });
                }

                var confirmButton = document.getElementById('gallery_delete_btn');
                if (confirmButton) {
                    confirmButton.addEventListener('click', function () {
                        deletePending();
                        modal && modal.modal('hide');
                    });
                }

                grid.addEventListener('change', function (event) {
                    if (event.target.classList.contains('media-desc-input')) {
                        saveDescription(event.target);
                    }
                });
                grid.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' && event.target.classList.contains('media-desc-input')) {
                        event.preventDefault();
                        event.target.blur();
                    }
                });

                grid.addEventListener('dragstart', function (event) {
                    var item = event.target.closest('.media-item');
                    if (item) {
                        item.classList.add('is-dragging');
                        event.dataTransfer.effectAllowed = 'move';
                    }
                });
                grid.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    var dragging = grid.querySelector('.media-item.is-dragging');
                    var target = event.target.closest('.media-item');
                    if (!dragging || !target || target === dragging) {
                        return;
                    }
                    var box = target.getBoundingClientRect();
                    var centerY = box.top + box.height / 2;
                    var insertAfter = event.clientY > centerY
                        || (Math.abs(event.clientY - centerY) < box.height / 2 && event.clientX > box.left + box.width / 2);
                    grid.insertBefore(dragging, insertAfter ? target.nextSibling : target);
                });
                grid.addEventListener('dragend', function (event) {
                    var item = event.target.closest('.media-item');
                    if (item) {
                        item.classList.remove('is-dragging');
                        saveOrder();
                    }
                });
            })();
        </script>
    @endif
@stop
