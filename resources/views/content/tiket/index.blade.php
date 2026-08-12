@extends('layouts/contentNavbarLayout')

@section('title', 'Tiket Gangguan')

@section('content')
<h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Operasional /</span> Tiket Gangguan</h4>

@if(!$isPelanggan)
<!-- Stats Cards -->
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card shadow-sm border-0 bg-white">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small d-block mb-1">Total Tiket</span>
            <h4 class="mb-0 fw-bold text-dark">{{ $tiket->count() }}</h4>
          </div>
          <span class="badge bg-label-primary p-2 rounded">
            <i class="bx bx-ticket text-primary" style="font-size: 1.5rem;"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-sm-6 col-xl-3">
    <div class="card shadow-sm border-0 bg-white">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small d-block mb-1">Status Open</span>
            <h4 class="mb-0 fw-bold text-danger">{{ $tiket->where('status', 'Open')->count() }}</h4>
          </div>
          <span class="badge bg-label-danger p-2 rounded">
            <i class="bx bx-error-circle text-danger" style="font-size: 1.5rem;"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card shadow-sm border-0 bg-white">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small d-block mb-1">In Progress</span>
            <h4 class="mb-0 fw-bold text-warning">{{ $tiket->where('status', 'In Progress')->count() }}</h4>
          </div>
          <span class="badge bg-label-warning p-2 rounded">
            <i class="bx bx-cog text-warning" style="font-size: 1.5rem;"></i>
          </span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="card shadow-sm border-0 bg-white">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-muted small d-block mb-1">Selesai (Closed)</span>
            <h4 class="mb-0 fw-bold text-success">{{ $tiket->where('status', 'Closed')->count() }}</h4>
          </div>
          <span class="badge bg-label-success p-2 rounded">
            <i class="bx bx-check-circle text-success" style="font-size: 1.5rem;"></i>
          </span>
        </div>
      </div>
    </div>
  </div>
</div>
@endif

<div class="card shadow-sm border-0 bg-white">
  <div class="card-header d-flex justify-content-between align-items-center bg-transparent border-bottom py-3">
    <div class="d-flex align-items-center">
      <div class="avatar me-2">
        <span class="avatar-initial rounded bg-label-primary"><i class="bx bx-support text-primary" style="font-size: 1.5rem;"></i></span>
      </div>
      <div>
        <h5 class="mb-0 fw-bold text-dark">Data Tiket Gangguan</h5>
        <small class="text-muted">Keluhan pelanggan dan alokasi teknisi</small>
      </div>
    </div>
    <a href="{{ route('tiket.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1">
      <i class="bx bx-plus"></i> Buat Tiket
    </a>
  </div>
  
  <div class="table-responsive text-nowrap">
    <table class="table table-hover align-middle">
      <thead>
        <tr class="table-light">
          <th>Kode</th>
          <th>Pelanggan</th>
          <th>Keluhan</th>
          <th>Teknisi</th>
          <th>Prioritas</th>
          <th>Status</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tiket as $t)
        <tr>
          <td class="fw-semibold text-primary">{{ $t->kode_tiket }}</td>
          <td>
            <div class="d-flex flex-column">
              <span class="fw-bold text-dark">{{ $t->pelanggan->nama_pelanggan }}</span>
              <small class="text-muted">{{ $t->pelanggan->no_wa ?: '-' }}</small>
            </div>
          </td>
          <td>
            <span class="text-wrap d-block" style="max-width: 250px;"><small class="text-secondary">{{ $t->keluhan }}</small></span>
          </td>
          <td>
            @if($isAdmin)
            <!-- Admin direct assignment selectbox -->
            <form action="{{ route('tiket.assign-teknisi', $t->id_tiket) }}" method="POST" class="d-inline">
              @csrf
              <select name="id_teknisi" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block border-0 bg-light fw-semibold text-center" style="width: auto; border-radius: 8px;">
                <option value="">-- Assign Teknisi --</option>
                @foreach($allTeknisi as $tech)
                  <option value="{{ $tech->id_teknisi }}" {{ $t->id_teknisi == $tech->id_teknisi ? 'selected' : '' }}>
                    👷 {{ $tech->nama_teknisi }}
                  </option>
                @endforeach
              </select>
            </form>
            @else
              @if($t->teknisi)
              <div class="d-flex align-items-center">
                <div class="avatar avatar-xs me-2">
                  <span class="avatar-initial rounded-circle bg-label-info">{{ substr($t->teknisi->nama_teknisi, 0, 1) }}</span>
                </div>
                <span class="fw-semibold">{{ $t->teknisi->nama_teknisi }}</span>
              </div>
              @else
              <span class="badge bg-label-secondary">Belum Di-Assign</span>
              @endif
            @endif
          </td>
          <td>
            @if($t->prioritas == 'High')
            <span class="badge bg-label-danger">High</span>
            @elseif($t->prioritas == 'Medium')
            <span class="badge bg-label-warning">Medium</span>
            @else
            <span class="badge bg-label-secondary">Low</span>
            @endif
          </td>
          <td>
            @if(!$isPelanggan)
            <form action="{{ route('tiket.status', $t->id_tiket) }}" method="POST" class="d-inline">
              @csrf
              <select name="status" onchange="this.form.submit()" class="form-select form-select-sm d-inline-block border-0 bg-light fw-semibold text-center" style="width: auto; border-radius: 8px;">
                <option value="Open" class="text-danger" {{ $t->status == 'Open' ? 'selected' : '' }}>🔴 Open</option>
                <option value="In Progress" class="text-warning" {{ $t->status == 'In Progress' ? 'selected' : '' }}>🟡 In Progress</option>
                <option value="Resolved" class="text-info" {{ $t->status == 'Resolved' ? 'selected' : '' }}>🔵 Resolved</option>
                <option value="Closed" class="text-success" {{ $t->status == 'Closed' ? 'selected' : '' }}>🟢 Closed</option>
              </select>
            </form>
            @else
            <span class="badge {{ $t->status == 'Closed' ? 'bg-label-success' : ($t->status == 'In Progress' ? 'bg-label-warning' : 'bg-label-danger') }}">{{ $t->status }}</span>
            @endif
          </td>
          <td>
            <div class="d-flex align-items-center justify-content-center gap-1">
              <!-- Chat Room Button -->
              @if($t->teknisi)
              <button type="button" class="btn btn-icon btn-sm btn-outline-primary btn-chat-trigger" 
                      data-tiket-id="{{ $t->id_tiket }}" 
                      data-kode="{{ $t->kode_tiket }}" 
                      data-teknisi="{{ $t->teknisi->nama_teknisi }}"
                      title="Chat Real-Time dengan Teknisi">
                <i class="bx bx-chat"></i>
              </button>
              @else
              <button type="button" class="btn btn-icon btn-sm btn-outline-secondary" disabled title="Assign teknisi dahulu untuk chat">
                <i class="bx bx-chat"></i>
              </button>
              @endif

              <!-- Quick Close Button -->
              @if($t->status !== 'Closed' && !$isPelanggan)
              <form action="{{ route('tiket.status', $t->id_tiket) }}" method="POST" class="d-inline">
                @csrf
                <input type="hidden" name="status" value="Closed">
                <button type="submit" class="btn btn-icon btn-sm btn-outline-success" title="Tandai Selesai (Closing)">
                  <i class="bx bx-check-circle"></i>
                </button>
              </form>
              @endif

              <!-- Delete Button -->
              @if($isAdmin)
              <button type="button" class="btn btn-icon btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $t->id_tiket }}" title="Hapus Tiket">
                <i class="bx bx-trash"></i>
              </button>

              <!-- Delete Modal -->
              <div class="modal fade" id="deleteModal{{ $t->id_tiket }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                  <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                      <h5 class="modal-title fw-bold">Konfirmasi</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body py-2 text-wrap" style="white-space: normal;">
                      Apakah Anda yakin ingin menghapus tiket <strong>{{ $t->kode_tiket }}</strong>?
                    </div>
                    <div class="modal-footer border-0 pt-0">
                      <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                      <form action="{{ route('tiket.destroy', $t->id_tiket) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="text-center py-4 text-muted">Tidak ada tiket gangguan ditemukan.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Premium Chat Room Modal -->
<div class="modal fade" id="chatModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 15px 50px rgba(0,0,0,0.15);">
      <!-- Modal Header -->
      <div class="modal-header bg-primary text-white d-flex align-items-center justify-content-between py-3 px-4">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-sm bg-label-white me-2">
            <span class="avatar-initial rounded-circle bg-white text-primary fw-bold" id="chat-avatar-initial">T</span>
          </div>
          <div>
            <h6 class="modal-title text-white fw-bold mb-0" id="chat-teknisi-name">Teknisi</h6>
            <small class="text-white-50" id="chat-ticket-code">TKT-XXXX</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Chat Box Body -->
      <div class="modal-body p-0 bg-light" style="height: 380px; overflow-y: auto;" id="chat-body">
        <div class="d-flex flex-column p-4 gap-3" id="chat-log">
          <!-- Chat bubbles will load dynamically -->
        </div>
      </div>

      <!-- Chat Image Preview Section -->
      <div id="chat-image-preview-container" class="bg-white border-top p-2 d-none align-items-center justify-content-between border-bottom">
        <div class="d-flex align-items-center">
          <img id="chat-image-preview" src="" class="rounded border me-2" style="height: 50px; width: 50px; object-fit: cover;">
          <span class="text-muted small text-truncate" id="chat-image-name" style="max-width: 250px;">gambar.png</span>
        </div>
        <button type="button" id="chat-image-cancel" class="btn-close btn-sm" aria-label="Cancel" style="font-size: 0.8rem;"></button>
      </div>

      <!-- Chat Box Input Footer -->
      <div class="modal-footer p-2 bg-white border-top border-light">
        <form id="chat-form" class="w-100 d-flex gap-2 align-items-center" enctype="multipart/form-data">
          @csrf
          <!-- Camera attachment trigger hidden input -->
          <input type="file" id="chat-image-input" class="d-none" accept="image/*">
          <button type="button" id="chat-image-btn" class="btn btn-outline-secondary p-2 d-flex align-items-center justify-content-center" style="border-radius: 50%; width: 40px; height: 40px;" title="Lampirkan Gambar (LOS/Kabel/Kendala)">
            <i class="bx bx-camera fs-5"></i>
          </button>
          
          <input type="text" id="chat-input" class="form-control border-light-subtle shadow-none py-2" placeholder="Tulis pesan..." autocomplete="off">
          <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center p-2" style="border-radius: 50%; width: 40px; height: 40px;">
            <i class="bx bx-paper-plane fs-5"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('page-style')
<style>
  /* Chat bubbles styles */
  .chat-bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 16px;
    position: relative;
    font-size: 0.9rem;
    line-height: 1.4;
    word-break: break-word;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  }
  
  .chat-bubble.sent {
    background: linear-gradient(135deg, #696cff 0%, #7d80ff 100%);
    color: #fff;
    border-bottom-right-radius: 4px;
    align-self: flex-end;
  }
  
  .chat-bubble.received {
    background: #fff;
    color: #333;
    border-bottom-left-radius: 4px;
    align-self: flex-start;
  }

  .chat-time {
    font-size: 0.7rem;
    display: block;
    margin-top: 4px;
    text-align: right;
  }

  .chat-bubble.sent .chat-time {
    color: rgba(255,255,255,0.75);
  }

  .chat-bubble.received .chat-time {
    color: #999;
  }

  .chat-sender-name {
    font-size: 0.72rem;
    font-weight: 700;
    color: #696cff;
    margin-bottom: 2px;
    display: block;
  }
</style>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentTiketId = null;
    let pollInterval = null;
    const chatModal = new bootstrap.Modal(document.getElementById('chatModal'));
    const chatLog = document.getElementById('chat-log');
    const chatBody = document.getElementById('chat-body');
    const chatInput = document.getElementById('chat-input');
    const chatForm = document.getElementById('chat-form');
    
    // Image attachment variables
    const imageInput = document.getElementById('chat-image-input');
    const imageBtn = document.getElementById('chat-image-btn');
    const previewContainer = document.getElementById('chat-image-preview-container');
    const previewImg = document.getElementById('chat-image-preview');
    const previewName = document.getElementById('chat-image-name');
    const previewCancel = document.getElementById('chat-image-cancel');

    // Trigger chat modal
    document.querySelectorAll('.btn-chat-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            currentTiketId = this.getAttribute('data-tiket-id');
            const code = this.getAttribute('data-kode');
            const name = this.getAttribute('data-teknisi');

            // Set Header info
            document.getElementById('chat-ticket-code').textContent = code;
            document.getElementById('chat-teknisi-name').textContent = name;
            document.getElementById('chat-avatar-initial').textContent = name.charAt(0).toUpperCase();

            // Clear previous log, file input, and previews
            chatLog.innerHTML = '<div class="text-center text-muted small my-3"><span class="spinner-border spinner-border-sm me-1"></span>Memuat chat...</div>';
            clearImageAttachment();
            
            // Open modal
            chatModal.show();

            // Load messages instantly
            fetchChats();

            // Start polling every 2 seconds for real-time updates
            clearInterval(pollInterval);
            pollInterval = setInterval(fetchChats, 2000);
        });
    });

    // Camera attachment trigger
    imageBtn.addEventListener('click', function() {
        imageInput.click();
    });

    // Handle file selection preview
    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            previewName.textContent = file.name;
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.classList.remove('d-none');
                previewContainer.classList.add('d-flex');
            }
            reader.readAsDataURL(file);
        } else {
            clearImageAttachment();
        }
    });

    // Cancel file attachment button
    previewCancel.addEventListener('click', function() {
        clearImageAttachment();
    });

    function clearImageAttachment() {
        imageInput.value = '';
        previewImg.src = '';
        previewName.textContent = '';
        previewContainer.classList.remove('d-flex');
        previewContainer.classList.add('d-none');
    }

    // Clear interval when chat modal is closed
    document.getElementById('chatModal').addEventListener('hidden.bs.modal', function () {
        clearInterval(pollInterval);
        currentTiketId = null;
        clearImageAttachment();
    });

    // Fetch chat history
    function fetchChats() {
        if (!currentTiketId) return;

        fetch(`/tiket/${currentTiketId}/chats`)
            .then(res => res.json())
            .then(data => {
                const isAtBottom = chatBody.scrollHeight - chatBody.scrollTop <= chatBody.clientHeight + 40;
                
                chatLog.innerHTML = '';
                if (data.length === 0) {
                    chatLog.innerHTML = '<div class="text-center text-muted small my-4">Belum ada obrolan. Silakan mulai chat dengan teknisi.</div>';
                    return;
                }

                data.forEach(msg => {
                    const bubble = document.createElement('div');
                    bubble.className = `chat-bubble ${msg.is_me ? 'sent' : 'received'}`;
                    
                    let senderHeader = '';
                    if (!msg.is_me) {
                        senderHeader = `<span class="chat-sender-name">${msg.user_name} (${msg.role})</span>`;
                    }

                    let imgHtml = '';
                    if (msg.image_url) {
                        imgHtml = `<a href="${msg.image_url}" target="_blank"><img src="${msg.image_url}" class="img-fluid rounded border shadow-sm my-1 d-block" style="max-height: 180px; object-fit: contain; cursor: zoom-in;" title="Buka Gambar"></a>`;
                    }

                    let msgTextHtml = msg.message ? `<div>${escapeHtml(msg.message)}</div>` : '';

                    bubble.innerHTML = `
                        ${senderHeader}
                        ${imgHtml}
                        ${msgTextHtml}
                        <span class="chat-time">${msg.time}</span>
                    `;
                    chatLog.appendChild(bubble);
                });

                // Scroll to bottom on load or if they were already at the bottom
                if (isAtBottom || chatLog.children.length <= data.length) {
                    chatBody.scrollTop = chatBody.scrollHeight;
                }
            })
            .catch(err => console.error('Error fetching chats:', err));
    }

    // Send chat message (handles text & optional image uploads via FormData)
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = chatInput.value.trim();
        const imageFile = imageInput.files[0];

        if (!text && !imageFile) return;

        chatInput.value = '';
        
        // Prepare multipart FormData
        const formData = new FormData();
        if (text) formData.append('message', text);
        if (imageFile) formData.append('image', imageFile);

        clearImageAttachment();

        fetch(`/tiket/${currentTiketId}/chats`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Clear the empty chat notification if it exists
                if (chatLog.innerHTML.includes('Belum ada obrolan')) {
                    chatLog.innerHTML = '';
                }

                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble sent';
                
                let imgHtml = '';
                if (data.chat.image_url) {
                    imgHtml = `<a href="${data.chat.image_url}" target="_blank"><img src="${data.chat.image_url}" class="img-fluid rounded border shadow-sm my-1 d-block" style="max-height: 180px; object-fit: contain; cursor: zoom-in;" title="Buka Gambar"></a>`;
                }

                let msgTextHtml = data.chat.message ? `<div>${escapeHtml(data.chat.message)}</div>` : '';

                bubble.innerHTML = `
                    ${imgHtml}
                    ${msgTextHtml}
                    <span class="chat-time">${data.chat.time}</span>
                `;
                chatLog.appendChild(bubble);
                chatBody.scrollTop = chatBody.scrollHeight;
            } else if (data.error) {
                alert(data.error);
            }
        })
        .catch(err => {
            console.error('Error sending chat:', err);
            alert('Gagal mengirim pesan.');
        });
    });

    // Helper function to escape HTML
    function escapeHtml(str) {
        return str
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>
@endsection
