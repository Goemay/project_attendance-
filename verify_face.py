# verify_face.py (InsightFace version — no dlib needed)
# pip install "numpy<2" insightface onnxruntime pillow
import sys, json, numpy as np
from PIL import Image
import insightface

def load_rgb(path):
    img = Image.open(path).convert("RGB")
    return np.array(img)

def enc(model, path):
    arr = load_rgb(path)
    face = model.get(arr)
    if not face:
        return None
    return face[0].normed_embedding  # L2-normalized 512-D vector

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print(json.dumps({"error":"args"})); sys.exit(1)

    # Load once; insightface will download a default model on first run
    app = insightface.app.FaceAnalysis(name="buffalo_l", providers=["CPUExecutionProvider"])
    app.prepare(ctx_id=0, det_size=(640,640))

    e1 = enc(app, sys.argv[1])
    e2 = enc(app, sys.argv[2])
    if e1 is None or e2 is None:
        print(json.dumps({"match": False, "distance": None, "error":"no_face"})); sys.exit(0)

    # For normalized embeddings, cosine distance = 1 - cosine similarity
    cos_sim = float(np.dot(e1, e2))
    match = cos_sim >= 0.35   # typical threshold ~0.3–0.5; tune as needed
    print(json.dumps({"match": match, "distance": 1.0 - cos_sim}))
