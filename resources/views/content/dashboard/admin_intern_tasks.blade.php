@extends('layouts/contentNavbarLayout')

@section('title', 'Manajemen Task List Magang')

@section('content')
<div class="row">
    <!-- Header -->
    <div class="col-lg-12 mb-4">
        <div class="card border-0 shadow-sm text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius: 16px;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="card-title text-white mb-2 fw-bold"><i class="bx bx-shield-quarter me-2"></i> Kontrol Task List Magang</h4>
                    <p class="mb-0 text-white-50">Kelola dan pantau seluruh aktivitas harian siswa magang melalui papan kontrol Kanban di bawah ini.</p>
                </div>
                <!-- Add Task Button to trigger Modal -->
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                    <i class="bx bx-plus me-1"></i> Buat Tugas Baru
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FILTER & SELECTOR -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm p-2" style="border-radius: 12px;">
            <div class="d-flex align-items-center gap-2 px-2">
                <i class="bx bx-filter-alt text-primary fs-4"></i>
                <select id="intern-filter" class="form-select border-0 text-dark fw-bold bg-transparent" style="box-shadow: none;">
                    <option value="all">Semua Siswa Magang ({{ $interns->count() }})</option>
                    @foreach($interns as $intern)
                        <option value="user-{{ $intern->id }}">{{ $intern->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

<!-- KANBAN BOARD FOR ADMIN -->
<div class="row">
    <!-- Column 1: TO DO -->
    <div class="col-md-4 mb-4">
        <div class="card bg-transparent border-0 h-100">
            <div class="card-header bg-transparent border-0 p-0 mb-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark">
                    <span class="badge bg-label-secondary me-2 p-2 rounded-circle" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bx bx-list-ul text-secondary"></i>
                    </span>
                    Belum Mulai
                </h5>
                <span class="badge bg-secondary rounded-pill px-3 py-1 text-dark fw-semibold" id="todo-count">{{ $tasks->where('status', 'todo')->count() }}</span>
            </div>
            <div class="kanban-column p-3 rounded-4" id="todo-column" data-status="todo" ondragover="allowDrop(event)" ondrop="drop(event)" style="border-radius: 16px;">
                <div class="d-flex flex-column gap-3" id="todo-items">
                    @foreach($tasks->where('status', 'todo') as $task)
                        <div class="kanban-card p-3 rounded-3 shadow-sm bg-white cursor-grab position-relative task-card" id="task-{{ $task->id }}" data-user-id="user-{{ $task->user_id }}" draggable="true" ondragstart="drag(event)" style="border-radius: 12px;">
                            <div class="task-circle bg-secondary"></div>
                            <span class="badge bg-label-primary mb-2 small fw-semibold user-badge" style="background-color: rgba(105, 108, 255, 0.12) !important; color: #696cff !important;">{{ $task->user->name ?? 'Magang' }}</span>
                            <p class="mb-0 text-dark small fw-medium mt-1">{{ $task->task }}</p>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <span class="text-muted small"><i class="bx bx-time-five me-1"></i>{{ $task->created_at->format('d M H:i') }}</span>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-icon text-primary border-0 bg-transparent" onclick="editTaskModal({{ $task->id }}, '{{ addslashes($task->task) }}', '{{ $task->status }}', {{ $task->user_id }})">
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon text-danger border-0 bg-transparent" onclick="deleteTask({{ $task->id }})">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Column 2: IN PROGRESS -->
    <div class="col-md-4 mb-4">
        <div class="card bg-transparent border-0 h-100">
            <div class="card-header bg-transparent border-0 p-0 mb-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark">
                    <span class="badge bg-label-warning me-2 p-2 rounded-circle" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bx bx-run text-warning"></i>
                    </span>
                    Sedang Dikerjakan
                </h5>
                <span class="badge bg-warning rounded-pill px-3 py-1 fw-semibold" id="progress-count">{{ $tasks->where('status', 'progress')->count() }}</span>
            </div>
            <div class="kanban-column p-3 rounded-4" id="progress-column" data-status="progress" ondragover="allowDrop(event)" ondrop="drop(event)" style="border-radius: 16px;">
                <div class="d-flex flex-column gap-3" id="progress-items">
                    @foreach($tasks->where('status', 'progress') as $task)
                        <div class="kanban-card p-3 rounded-3 shadow-sm bg-white cursor-grab position-relative task-card" id="task-{{ $task->id }}" data-user-id="user-{{ $task->user_id }}" draggable="true" ondragstart="drag(event)" style="border-radius: 12px;">
                            <div class="task-circle bg-warning animate-pulse"></div>
                            <span class="badge bg-label-primary mb-2 small fw-semibold user-badge" style="background-color: rgba(105, 108, 255, 0.12) !important; color: #696cff !important;">{{ $task->user->name ?? 'Magang' }}</span>
                            <p class="mb-0 text-dark small fw-medium mt-1">{{ $task->task }}</p>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <span class="text-muted small"><i class="bx bx-time-five me-1"></i>{{ $task->created_at->format('d M H:i') }}</span>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-icon text-primary border-0 bg-transparent" onclick="editTaskModal({{ $task->id }}, '{{ addslashes($task->task) }}', '{{ $task->status }}', {{ $task->user_id }})">
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon text-danger border-0 bg-transparent" onclick="deleteTask({{ $task->id }})">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Column 3: DONE -->
    <div class="col-md-4 mb-4">
        <div class="card bg-transparent border-0 h-100">
            <div class="card-header bg-transparent border-0 p-0 mb-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-dark">
                    <span class="badge bg-label-success me-2 p-2 rounded-circle" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bx bx-check-double text-success"></i>
                    </span>
                    Selesai
                </h5>
                <span class="badge bg-success rounded-pill px-3 py-1 fw-semibold" id="done-count">{{ $tasks->where('status', 'done')->count() }}</span>
            </div>
            <div class="kanban-column p-3 rounded-4" id="done-column" data-status="done" ondragover="allowDrop(event)" ondrop="drop(event)" style="border-radius: 16px;">
                <div class="d-flex flex-column gap-3" id="done-items">
                    @foreach($tasks->where('status', 'done') as $task)
                        <div class="kanban-card p-3 rounded-3 shadow-sm bg-white cursor-grab position-relative task-card" id="task-{{ $task->id }}" data-user-id="user-{{ $task->user_id }}" draggable="true" ondragstart="drag(event)" style="border-radius: 12px;">
                            <div class="task-circle bg-success"></div>
                            <span class="badge bg-label-primary mb-2 small fw-semibold user-badge" style="background-color: rgba(105, 108, 255, 0.12) !important; color: #696cff !important;">{{ $task->user->name ?? 'Magang' }}</span>
                            <p class="mb-0 text-muted small fw-medium mt-1 text-decoration-line-through">{{ $task->task }}</p>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <span class="text-muted small"><i class="bx bx-check-circle me-1 text-success"></i>Selesai</span>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-icon text-primary border-0 bg-transparent" onclick="editTaskModal({{ $task->id }}, '{{ addslashes($task->task) }}', '{{ $task->status }}', {{ $task->user_id }})">
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon text-danger border-0 bg-transparent" onclick="deleteTask({{ $task->id }})">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD TASK MODAL -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="add-task-form" class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold" id="exampleModalLabel1"><i class="bx bx-task me-1 text-primary"></i> Tambah Tugas Magang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col mb-3">
                        <label for="assignee-select" class="form-label fw-semibold">Pilih Siswa Magang</label>
                        <select id="assignee-select" class="form-select border-light bg-light" required>
                            <option value="">-- Pilih Siswa --</option>
                            @foreach($interns as $intern)
                                <option value="{{ $intern->id }}">{{ $intern->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-0">
                        <label for="task-description" class="form-label fw-semibold">Deskripsi Tugas</label>
                        <textarea id="task-description" class="form-control border-light bg-light" rows="3" placeholder="Contoh: Periksa ketersediaan kabel fiber optik di gudang..." required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top p-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Tugas</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT TASK MODAL -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="edit-task-form" class="modal-content" style="border-radius: 16px; border: none; overflow: hidden;">
            <input type="hidden" id="edit-task-id">
            <div class="modal-header border-bottom p-4">
                <h5 class="modal-title fw-bold"><i class="bx bx-edit me-1 text-primary"></i> Edit Tugas Magang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col mb-3">
                        <label for="edit-assignee-select" class="form-label fw-semibold">Pilih Siswa Magang</label>
                        <select id="edit-assignee-select" class="form-select border-light bg-light" required>
                            @foreach($interns as $intern)
                                <option value="{{ $intern->id }}">{{ $intern->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label for="edit-status-select" class="form-label fw-semibold">Status Tugas</label>
                        <select id="edit-status-select" class="form-select border-light bg-light" required>
                            <option value="todo">Belum Mulai</option>
                            <option value="progress">Sedang Dikerjakan</option>
                            <option value="done">Selesai</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col mb-0">
                        <label for="edit-task-description" class="form-label fw-semibold">Deskripsi Tugas</label>
                        <textarea id="edit-task-description" class="form-control border-light bg-light" rows="3" required></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top p-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<style>
    /* Premium Drag & Drop Aesthetics */
    .kanban-column {
        background-color: rgba(243, 244, 246, 0.7);
        border: 2px dashed rgba(229, 231, 235, 0.8);
        min-height: 480px;
        transition: all 0.2s ease;
    }
    
    .kanban-column.dragover {
        background-color: rgba(224, 231, 255, 0.7);
        border-color: #6366f1;
        transform: scale(1.01);
    }

    .kanban-card {
        border-left: 4px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .task-circle {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    
    .animate-pulse {
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.2); }
    }

    #todo-column .kanban-card {
        border-left-color: #9ca3af;
    }
    #progress-column .kanban-card {
        border-left-color: #fbbf24;
    }
    #done-column .kanban-card {
        border-left-color: #10b981;
    }

    .kanban-card:hover {
        transform: translateY(-3px) scale(1.01);
        box-shadow: 0 10px 20px -8px rgba(0,0,0,0.1) !important;
    }

    .kanban-card.dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }
    
    .cursor-grab {
        cursor: grab;
    }
    .cursor-grab:active {
        cursor: grabbing;
    }
    
    .bg-light {
        background-color: #f3f4f6 !important;
    }
    
    .bg-label-secondary {
        background-color: rgba(140, 140, 161, 0.15) !important;
    }
    .bg-label-warning {
        background-color: rgba(255, 171, 0, 0.15) !important;
    }
    .bg-label-success {
        background-color: rgba(113, 221, 55, 0.15) !important;
    }
</style>

<script>
    // FILTER DYNAMICALLY BY INTERN
    const internFilter = document.getElementById('intern-filter');
    internFilter.addEventListener('change', function() {
        const filterVal = this.value;
        const cards = document.querySelectorAll('.task-card');
        
        cards.forEach(card => {
            if (filterVal === 'all' || card.getAttribute('data-user-id') === filterVal) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        updateColumnCounters();
    });

    // DRAG AND DROP
    function drag(ev) {
        ev.dataTransfer.setData("text/plain", ev.target.id);
        ev.target.classList.add('dragging');
    }

    function allowDrop(ev) {
        ev.preventDefault();
        const column = ev.target.closest('.kanban-column');
        if (column) {
            column.classList.add('dragover');
        }
    }

    document.querySelectorAll('.kanban-column').forEach(column => {
        column.addEventListener('dragleave', () => {
            column.classList.remove('dragover');
        });
    });

    function drop(ev) {
        ev.preventDefault();
        const column = ev.target.closest('.kanban-column');
        if (!column) return;

        column.classList.remove('dragover');
        const idData = ev.dataTransfer.getData("text/plain");
        const draggedCard = document.getElementById(idData);
        if (!draggedCard) return;

        draggedCard.classList.remove('dragging');
        
        const newStatus = column.getAttribute('data-status');
        const taskId = idData.replace('task-', '');

        // Move DOM
        const targetContainer = column.querySelector('.d-flex');
        targetContainer.appendChild(draggedCard);

        // UI Updates
        const circle = draggedCard.querySelector('.task-circle');
        const text = draggedCard.querySelector('p');
        const dateSpan = draggedCard.querySelector('.text-muted');

        if (newStatus === 'todo') {
            circle.className = 'task-circle bg-secondary';
            text.className = 'mb-0 text-dark small fw-medium mt-1';
        } else if (newStatus === 'progress') {
            circle.className = 'task-circle bg-warning animate-pulse';
            text.className = 'mb-0 text-dark small fw-medium mt-1';
        } else if (newStatus === 'done') {
            circle.className = 'task-circle bg-success';
            text.className = 'mb-0 text-muted small fw-medium mt-1 text-decoration-line-through';
        }

        // AJAX update status
        fetch(`/intern/tasks/${taskId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateColumnCounters();
            }
        })
        .catch(err => console.error(err));
    }

    function updateColumnCounters() {
        const filterVal = internFilter.value;
        
        // Count visible cards per column
        const getVisibleCount = (selector) => {
            let count = 0;
            document.querySelectorAll(selector).forEach(card => {
                if (card.style.display !== 'none') count++;
            });
            return count;
        };

        const todoCount = getVisibleCount('#todo-items .task-card');
        const progressCount = getVisibleCount('#progress-items .task-card');
        const doneCount = getVisibleCount('#done-items .task-card');

        document.getElementById('todo-count').textContent = todoCount;
        document.getElementById('progress-count').textContent = progressCount;
        document.getElementById('done-count').textContent = doneCount;
    }

    // CREATE TASK (ADMIN AJAX)
    const addTaskForm = document.getElementById('add-task-form');
    addTaskForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const userId = document.getElementById('assignee-select').value;
        const taskText = document.getElementById('task-description').value;

        if (!userId || !taskText) return;

        fetch('{{ route("admin.intern-tasks.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                user_id: userId,
                task: taskText
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
                modal.hide();
                addTaskForm.reset();

                // Reload page to re-render in Kanban properly
                location.reload();
            }
        })
        .catch(err => console.error(err));
    });

    // EDIT TASK MODAL TRIGGER
    function editTaskModal(id, task, status, userId) {
        document.getElementById('edit-task-id').value = id;
        document.getElementById('edit-task-description').value = task;
        document.getElementById('edit-assignee-select').value = userId;
        document.getElementById('edit-status-select').value = status;
        
        const editModal = new bootstrap.Modal(document.getElementById('editTaskModal'));
        editModal.show();
    }

    // UPDATE TASK (ADMIN AJAX)
    const editTaskForm = document.getElementById('edit-task-form');
    editTaskForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const id = document.getElementById('edit-task-id').value;
        const userId = document.getElementById('edit-assignee-select').value;
        const status = document.getElementById('edit-status-select').value;
        const taskText = document.getElementById('edit-task-description').value;

        fetch(`/admin/intern-tasks/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                user_id: userId,
                status: status,
                task: taskText
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                location.reload();
            }
        })
        .catch(err => console.error(err));
    });

    // DELETE TASK (ADMIN AJAX)
    function deleteTask(id) {
        if (!confirm('Hapus tugas ini secara permanen?')) return;

        fetch(`/admin/intern-tasks/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const card = document.getElementById(`task-${id}`);
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        card.remove();
                        updateColumnCounters();
                    }, 300);
                }
            }
        })
        .catch(err => console.error(err));
    }
</script>
@endsection
