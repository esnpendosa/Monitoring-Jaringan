@extends('layouts/contentNavbarLayout')

@section('title', 'Workspace Magang')

@section('content')
<div class="row">
    <!-- Welcome Card -->
    <div class="col-lg-12 mb-4">
        <div class="card border-0 shadow-sm text-white position-relative overflow-hidden" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius: 16px;">
            <!-- Design pattern overlay -->
            <div class="position-absolute end-0 bottom-0 opacity-10" style="font-size: 15rem; transform: translate(10%, 20%); line-height: 1;">
                <i class="bx bx-circle-quarter"></i>
            </div>
            <div class="d-flex align-items-end row">
                <div class="col-sm-8">
                    <div class="card-body p-4">
                        <h4 class="card-title text-white mb-2 fw-bold">Workspace Magang, {{ auth()->user()->name }}! 🎓</h4>
                        <p class="mb-0 text-white-50">Selesaikan tugas harian Anda dengan **menggeser (drag & drop)** kartu tugas ke kolom yang sesuai di bawah ini.</p>
                    </div>
                </div>
                <div class="col-sm-4 text-center text-sm-start d-none d-sm-block">
                    <div class="card-body pb-0 px-0 px-md-4 text-center">
                        <img src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}" height="110" alt="Intern Welcome" class="img-fluid animate-bounce">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- KANBAN BOARD -->
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
            <div class="kanban-column p-3 rounded-4 shadow-sm" id="todo-column" data-status="todo" ondragover="allowDrop(event)" ondrop="drop(event)" style="border-radius: 16px;">
                <div class="d-flex flex-column gap-3" id="todo-items">
                    @foreach($tasks->where('status', 'todo') as $task)
                        <div class="kanban-card p-3 rounded-3 shadow-sm bg-white cursor-grab position-relative" id="task-{{ $task->id }}" draggable="true" ondragstart="drag(event)" style="border-radius: 12px;">
                            <div class="task-circle bg-secondary"></div>
                            <p class="mb-0 text-dark small fw-medium mt-1">{{ $task->task }}</p>
                            <span class="text-muted small mt-2 d-block"><i class="bx bx-time-five me-1"></i>{{ $task->created_at->format('d M H:i') }}</span>
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
            <div class="kanban-column p-3 rounded-4 shadow-sm" id="progress-column" data-status="progress" ondragover="allowDrop(event)" ondrop="drop(event)" style="border-radius: 16px;">
                <div class="d-flex flex-column gap-3" id="progress-items">
                    @foreach($tasks->where('status', 'progress') as $task)
                        <div class="kanban-card p-3 rounded-3 shadow-sm bg-white cursor-grab position-relative" id="task-{{ $task->id }}" draggable="true" ondragstart="drag(event)" style="border-radius: 12px;">
                            <div class="task-circle bg-warning animate-pulse"></div>
                            <p class="mb-0 text-dark small fw-medium mt-1">{{ $task->task }}</p>
                            <span class="text-muted small mt-2 d-block"><i class="bx bx-run me-1 text-warning"></i>Dikerjakan</span>
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
            <div class="kanban-column p-3 rounded-4 shadow-sm" id="done-column" data-status="done" ondragover="allowDrop(event)" ondrop="drop(event)" style="border-radius: 16px;">
                <div class="d-flex flex-column gap-3" id="done-items">
                    @foreach($tasks->where('status', 'done') as $task)
                        <div class="kanban-card p-3 rounded-3 shadow-sm bg-white cursor-grab position-relative" id="task-{{ $task->id }}" draggable="true" ondragstart="drag(event)" style="border-radius: 12px;">
                            <div class="task-circle bg-success"></div>
                            <p class="mb-0 text-muted small fw-medium mt-1 text-decoration-line-through">{{ $task->task }}</p>
                            <span class="text-muted small mt-2 d-block"><i class="bx bx-check-circle me-1 text-success"></i>Selesai</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Kanban Drag & Drop Premium Aesthetics */
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
    
    .animate-bounce {
        animation: bounce 3s infinite;
    }
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
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
        const dateSpan = draggedCard.querySelector('span');

        if (newStatus === 'todo') {
            circle.className = 'task-circle bg-secondary';
            text.className = 'mb-0 text-dark small fw-medium mt-1';
            dateSpan.innerHTML = `<i class="bx bx-time-five me-1"></i>Mulai Tugas`;
        } else if (newStatus === 'progress') {
            circle.className = 'task-circle bg-warning animate-pulse';
            text.className = 'mb-0 text-dark small fw-medium mt-1';
            dateSpan.innerHTML = `<i class="bx bx-run me-1 text-warning"></i>Dikerjakan`;
        } else if (newStatus === 'done') {
            circle.className = 'task-circle bg-success';
            text.className = 'mb-0 text-muted small fw-medium mt-1 text-decoration-line-through';
            dateSpan.innerHTML = `<i class="bx bx-check-circle me-1 text-success"></i>Selesai`;
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
        const todoCount = document.querySelectorAll('#todo-items .kanban-card').length;
        const progressCount = document.querySelectorAll('#progress-items .kanban-card').length;
        const doneCount = document.querySelectorAll('#done-items .kanban-card').length;

        document.getElementById('todo-count').textContent = todoCount;
        document.getElementById('progress-count').textContent = progressCount;
        document.getElementById('done-count').textContent = doneCount;
    }
</script>
@endsection
