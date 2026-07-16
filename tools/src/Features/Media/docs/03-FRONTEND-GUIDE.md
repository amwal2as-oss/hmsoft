# Media — Frontend Integration Guide

How to upload, delete, and display media from Vue/React CMS clients.

> **Full reference:** [../README.md](../README.md)

---

## Request formats

| Operation | Content-Type | Key fields |
|-------------|--------------|------------|
| Upload file | `multipart/form-data` | `{field}` = File |
| Delete file | `multipart/form-data` or JSON | `delete_{field}` = true |
| External URL | `multipart/form-data` or JSON | `{field}` = URL string |
| Media API upload | `multipart/form-data` | `file`, `media_type`, `locales` |

---

## Single image upload

### React example

```tsx
async function syncBlogImage(blogId: number, file: File, token: string) {
  const formData = new FormData();
  formData.append('image', file);

  const res = await fetch(`/api/blogs/${blogId}/sync-image`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
    body: formData,
  });

  return res.json();
}
```

### Vue / Axios example

```javascript
async function syncBlogImage(blogId, file) {
  const formData = new FormData();
  formData.append('image', file);

  const { data } = await axios.post(`/api/blogs/${blogId}/sync-image`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return data;
}
```

### With create (store + image together)

```typescript
const formData = new FormData();
formData.append('title', title);
formData.append('is_active', '1');
formData.append('image', imageFile);

await fetch('/api/blogs', { method: 'POST', body: formData });
```

---

## Delete image

```typescript
const formData = new FormData();
formData.append('delete_image', '1');

await fetch(`/api/blogs/${id}/sync-image`, { method: 'POST', body: formData });
```

Or JSON if your endpoint accepts it:

```json
{ "delete_image": true }
```

---

## Upload external URL (no file)

```typescript
formData.append('image', 'https://cdn.example.com/banner.jpg');
```

Backend stores URL in column; `image_url` returns it directly.

---

## Display images

### From `image_object` (recommended)

```tsx
interface MediaObject {
  url: string;
  thumb?: string;
  medium?: string;
  srcset?: string;
}

function BlogThumbnail({ image }: { image: MediaObject | null }) {
  if (!image?.url) return <PlaceholderImg />;

  return (
    <img
      src={image.thumb ?? image.url}
      srcSet={image.srcset}
      sizes="(max-width: 600px) 100vw, 300px"
      alt=""
      loading="lazy"
    />
  );
}
```

### From flat `image_url`

```tsx
<img src={blog.image_url ?? '/assets/placeholder.png'} alt={blog.title} />
```

### PDF / file link

```tsx
<a href={decree.pdf_url} target="_blank" rel="noopener">
  Download PDF
</a>
```

---

## PDF upload

Same pattern as image — field name matches backend (`pdf_path` or custom):

```typescript
formData.append('pdf', pdfFile);
await fetch(`/api/decrees/${id}/sync-pdf`, { method: 'POST', body: formData });
```

---

## Gallery / multiple files (polymorphic)

### Upload multiple attachments

```typescript
const formData = new FormData();
files.forEach((file, index) => {
  formData.append(`attachments[${index}][file]`, file);
});

await fetch(`/api/legislation/${id}/sync-files`, {
  method: 'POST',
  body: formData,
});
```

### Delete gallery items by ID

```typescript
formData.append('deleted_attachments_ids[0]', '12');
formData.append('deleted_attachments_ids[1]', '15');
// + optional new files
```

Field names must match `getGalleryRules('attachments')` prefix.

---

## Standalone Media API

### List media for entity

```typescript
const res = await fetch(
  `/api/blog/${blogId}/media?page=1&perPage=20&fields=id,file_url,media_type,is_default`
);
const { data, pagination } = await res.json();
```

### Upload to media table

```typescript
const formData = new FormData();
formData.append('file', file);
formData.append('media_type', 'gallery');
formData.append('is_default', 'false');
formData.append('locales[0][locale]', 'en');
formData.append('locales[0][title]', 'Gallery photo');
formData.append('locales[0][alt]', 'Alt text');

await fetch(`/api/blog/${blogId}/media`, { method: 'POST', body: formData });
```

### Delete media item

```typescript
await fetch(`/api/blog/${blogId}/media/${mediaId}`, { method: 'DELETE' });
```

### Bulk delete

```typescript
await fetch(`/api/blog/${blogId}/media/bulk-delete`, {
  method: 'DELETE',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ ids: [1, 2, 3] }),
});
```

---

## CMS form patterns

### Image picker component props

```typescript
type ImageFieldProps = {
  value?: MediaObject | null;
  onUpload: (file: File) => Promise<void>;
  onDelete: () => Promise<void>;
  onUrlPaste?: (url: string) => Promise<void>;
};
```

### Show upload status

Backend returns `media_status`:

| Value | Meaning |
|-------|---------|
| `uploaded` | New file saved |
| `deleted` | File removed |
| `unchanged` | No action taken |

```tsx
const { media_status } = await syncBlogImage(id, file);
toast.success(media_status === 'uploaded' ? 'Image saved' : 'No changes');
```

---

## File validation (client-side hints)

Match backend expectations:

| Type | Client check |
|------|--------------|
| Image | `image/jpeg`, `image/png`, `image/webp`, `image/gif` |
| PDF | `application/pdf` |
| Max size | Coordinate with backend `max:` rule in DTO |

Backend accepts URL strings in addition to files — useful for import/migration UIs.

---

## Common mistakes

| Mistake | Fix |
|---------|-----|
| Sending JSON for file upload | Use `FormData`, not `JSON.stringify` |
| Wrong field name | Must match model column / DTO (`image`, not `file`) |
| Missing `delete_image` flag | Explicit boolean to remove without replacement |
| Broken image URL | Ensure API returns `image` or `image_url` after refresh |
| CORS on storage URL | Storage disk must be publicly accessible or use proxy |
| Media API 404 | Check `owner_type` morph alias is registered |

---

## TypeScript types

```typescript
export interface MediaObject {
  url: string;
  thumb?: string;
  medium?: string;
  srcset?: string | null;
}

export interface MediumResponse {
  id: number;
  file_path: string;
  file_url: string;
  file_name: string;
  mime_type: string;
  media_type: string;
  is_default: boolean;
  sort_number: number;
  translations?: Record<string, {
    title?: string;
    alt?: string;
    short_description?: string;
  }>;
}

export type MediaSyncStatus = 'uploaded' | 'deleted' | 'unchanged';
```

---

## See also

- [02-BACKEND-INTEGRATION.md](./02-BACKEND-INTEGRATION.md)
- [04-SETUP-CHECKLIST.md](./04-SETUP-CHECKLIST.md)
