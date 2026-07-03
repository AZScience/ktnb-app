export const TABLE_EMPTY_FILTERED = 'Không có dữ liệu phù hợp.';
export const TABLE_EMPTY_NO_DATA = 'Chưa có dữ liệu.';
export const TABLE_LOADING = 'Đang tải dữ liệu...';
export const TABLE_EXPORT_EMPTY = 'Không có dữ liệu để xuất.';
export const TABLE_SUBTITLE_EMPTY = 'Chưa có bản ghi nào trong bảng này. Hãy thêm mới hoặc chọn điều kiện khác.';
export const TABLE_SUBTITLE_FILTERED = 'Không có bản ghi khớp với bộ lọc hiện tại. Hãy điều chỉnh hoặc xóa bộ lọc.';
export const TABLE_SUBTITLE_LOADING = 'Vui lòng đợi trong giây lát.';

export function resolveTableEmptyMessage({
    hasFilters = false,
    loading = false,
    customEmpty = null,
} = {}) {
    if (loading) {
        return TABLE_LOADING;
    }
    if (hasFilters) {
        return TABLE_EMPTY_FILTERED;
    }
    if (customEmpty) {
        return customEmpty;
    }

    return TABLE_EMPTY_NO_DATA;
}
