FROM python:3.10

# Set working directory
WORKDIR /var/www/TA_201_Flask

# Copy Flask files
COPY TA_201_Flask /var/www/TA_201_Flask

# Upgrade pip dan install dependencies
RUN pip install --upgrade pip setuptools wheel gunicorn

# Install dependencies dari `requirements.txt`
RUN pip install --no-cache-dir -r /var/www/TA_201_Flask/requirements.txt

RUN python -c "import nltk; \
               nltk.download('averaged_perceptron_tagger'); \
               nltk.download('wordnet'); \
               nltk.download('sentiwordnet'); \
               nltk.download('punkt')"

# Download model Spacy
RUN python -m spacy download en_core_web_sm

EXPOSE 5000

# Jalankan aplikasi Flask dengan virtual environment
# CMD ["gunicorn", "-b", "0.0.0.0:5000", "app:app"]
CMD ["gunicorn", "-b", "0.0.0.0:5000", "-w", "2", "app:app"]