    </div> <!-- End main-content -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Initialize DataTables on all tables with class 'dataTable'
        $(document).ready(function() {
            $('table.dataTable').each(function() {
                const table = $(this);
                const tableId = table.attr('id') || 'dataTable_' + Math.random().toString(36).substr(2, 9);
                table.attr('id', tableId);
                
                // Check if table has export buttons
                const hasExport = table.data('export') !== false;
                
                // Determine default sort column
                let defaultSort = [[0, 'desc']]; // Default: first column descending
                const firstColText = table.find('thead th').first().text().toLowerCase();
                if (firstColText.includes('id') || firstColText.includes('date') || firstColText.includes('created')) {
                    defaultSort = [[0, 'desc']]; // Sort by ID/Date descending
                } else {
                    defaultSort = [[0, 'asc']]; // Sort alphabetically for names
                }
                
                const config = {
                    dom: 'Bfrtip',
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    order: defaultSort,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "No entries to show",
                        infoFiltered: "(filtered from _MAX_ total entries)",
                        zeroRecords: "No matching records found"
                    },
                    responsive: true,
                    buttons: hasExport ? [
                        {
                            extend: 'copy',
                            text: '<i class="bi bi-clipboard"></i> Copy',
                            className: 'btn btn-sm btn-outline-secondary',
                            exportOptions: {
                                columns: ':visible'
                            }
                        },
                        {
                            extend: 'csv',
                            text: '<i class="bi bi-filetype-csv"></i> CSV',
                            className: 'btn btn-sm btn-outline-primary',
                            exportOptions: {
                                columns: ':visible'
                            },
                            filename: function() {
                                return 'export_' + new Date().toISOString().split('T')[0];
                            }
                        },
                        {
                            extend: 'excel',
                            text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                            className: 'btn btn-sm btn-outline-success',
                            exportOptions: {
                                columns: ':visible'
                            },
                            filename: function() {
                                return 'export_' + new Date().toISOString().split('T')[0];
                            }
                        },
                        {
                            extend: 'pdf',
                            text: '<i class="bi bi-filetype-pdf"></i> PDF',
                            className: 'btn btn-sm btn-outline-danger',
                            exportOptions: {
                                columns: ':visible'
                            },
                            orientation: 'landscape',
                            pageSize: 'A4',
                            filename: function() {
                                return 'export_' + new Date().toISOString().split('T')[0];
                            }
                        },
                        {
                            extend: 'print',
                            text: '<i class="bi bi-printer"></i> Print',
                            className: 'btn btn-sm btn-outline-info',
                            exportOptions: {
                                columns: ':visible'
                            }
                        }
                    ] : []
                };
                
                // Initialize DataTable
                table.DataTable(config);
            });
        });
    </script>
</body>
</html>
