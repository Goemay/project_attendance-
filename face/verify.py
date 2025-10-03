# face/verify.py
import argparse, json, os, sys, tempfile
import pymysql
from deepface import DeepFace

def db():
    return pymysql.connect(
        host=os.getenv("DB_HOST","127.0.0.1"),
        user=os.getenv("DB_USERNAME","root"),
        password=os.getenv("DB_PASSWORD",""),
        database=os.getenv("DB_DATABASE","project_cnaindo"),
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=True
    )

def load_user_photo(conn, user_id):
    with conn.cursor() as cur:
        cur.execute("SELECT faceimage FROM users WHERE id=%s", (user_id,))
        row = cur.fetchone()
        return row["faceimage"] if row and row.get("faceimage") else None

def save_blob_to_temp(blob_bytes):
    f = tempfile.NamedTemporaryFile(delete=False, suffix=".png")
    f.write(blob_bytes); f.close()
    return f.name

if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("--user-id", type=int, required=True)
    ap.add_argument("--image", required=True)
    args = ap.parse_args()

    conn = db()
    blob = load_user_photo(conn, args.user_id)
    if not blob:
        print(json.dumps({"match": False, "reason": "no_enrollment"})); sys.exit(0)

    enrolled_path = save_blob_to_temp(blob)
    try:
        res = DeepFace.verify(img1_path=enrolled_path, img2_path=args.image)
        print(json.dumps({"match": bool(res.get("verified")), "distance": float(res.get("distance", -1))}))
    except Exception:
        print(json.dumps({"match": False, "reason": "python_error"}))
    finally:
        try: os.unlink(enrolled_path)
        except Exception: pass
